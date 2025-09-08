<?php
// Database configuration
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // First try to use DATABASE_URL from Replit
        if (isset($_SERVER['DATABASE_URL'])) {
            $url = parse_url($_SERVER['DATABASE_URL']);
            $this->host = $url['host'];
            $this->db_name = ltrim($url['path'], '/');
            $this->username = $url['user'];
            $this->password = $url['pass'];
            $this->port = isset($url['port']) ? $url['port'] : '5432';
        } else {
            // Fallback to individual environment variables with Replit defaults
            $this->host = $_SERVER['PGHOST'] ?? getenv('PGHOST') ?: 'ep-dawn-pond-af0j98yz.c-2.us-west-2.aws.neon.tech';
            $this->db_name = $_SERVER['PGDATABASE'] ?? getenv('PGDATABASE') ?: 'neondb';
            $this->username = $_SERVER['PGUSER'] ?? getenv('PGUSER') ?: 'neondb_owner';
            $this->password = $_SERVER['PGPASSWORD'] ?? getenv('PGPASSWORD') ?: 'npg_OwYltEd9j3kL';
            $this->port = $_SERVER['PGPORT'] ?? getenv('PGPORT') ?: '5432';
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";sslmode=require";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }

    public function initializeDatabase() {
        $conn = $this->getConnection();
        if (!$conn) {
            return false;
        }

        try {
            // Check if tables exist
            $stmt = $conn->query("SELECT to_regclass('public.users')");
            $tableExists = $stmt->fetchColumn();
            
            if (!$tableExists) {
                $this->createTables($conn);
                $this->insertSampleData($conn);
            }
            return true;
        } catch(PDOException $e) {
            echo "Database initialization error: " . $e->getMessage();
            return false;
        }
    }

    private function createTables($conn) {
        $sql = "
        -- Create users table
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            role VARCHAR(20) DEFAULT 'author' CHECK (role IN ('admin', 'editor', 'author')),
            status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create categories table with subcategory support
        CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#007bff',
            display_order INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
            show_in_menu BOOLEAN DEFAULT true,
            show_on_homepage BOOLEAN DEFAULT false,
            homepage_priority INTEGER DEFAULT 0,
            parent_category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            is_subcategory BOOLEAN DEFAULT false,
            subcategory_order INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create articles table
        CREATE TABLE IF NOT EXISTS articles (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            excerpt TEXT,
            content TEXT NOT NULL,
            featured_image VARCHAR(255),
            featured_image_alt VARCHAR(255),
            category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            author_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
            is_featured BOOLEAN DEFAULT false,
            is_breaking BOOLEAN DEFAULT false,
            views INTEGER DEFAULT 0,
            published_at TIMESTAMP,
            scheduled_at TIMESTAMP,
            meta_title VARCHAR(255),
            meta_description TEXT,
            meta_keywords VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create comments table
        CREATE TABLE IF NOT EXISTS comments (
            id SERIAL PRIMARY KEY,
            article_id INTEGER REFERENCES articles(id) ON DELETE CASCADE,
            parent_id INTEGER REFERENCES comments(id) ON DELETE CASCADE,
            author_name VARCHAR(100) NOT NULL,
            author_email VARCHAR(100) NOT NULL,
            content TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create homepage_sections table
        CREATE TABLE IF NOT EXISTS homepage_sections (
            id SERIAL PRIMARY KEY,
            section_name VARCHAR(50) NOT NULL,
            section_title VARCHAR(100) NOT NULL,
            layout_style VARCHAR(20) DEFAULT 'grid' CHECK (layout_style IN ('banner', 'grid', 'list')),
            article_limit INTEGER DEFAULT 5,
            is_enabled BOOLEAN DEFAULT true,
            display_order INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create settings table
        CREATE TABLE IF NOT EXISTS settings (
            id SERIAL PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create category display settings table
        CREATE TABLE IF NOT EXISTS category_display_settings (
            id SERIAL PRIMARY KEY,
            category_id INTEGER REFERENCES categories(id) ON DELETE CASCADE,
            homepage_visible BOOLEAN DEFAULT true,
            display_order INTEGER DEFAULT 0,
            layout_type VARCHAR(50) DEFAULT 'grid',
            max_articles INTEGER DEFAULT 6,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ";

        $conn->exec($sql);
    }

    private function insertSampleData($conn) {
        // Insert sample users
        $conn->exec("
        INSERT INTO users (username, email, password, first_name, last_name, role, status) VALUES
        ('admin', 'admin@news.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active'),
        ('editor1', 'editor@news.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Editor', 'editor', 'active'),
        ('author1', 'author@news.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Author', 'author', 'active')
        ON CONFLICT (username) DO NOTHING;
        ");

        // Insert main categories
        $conn->exec("
        INSERT INTO categories (name, slug, description, color, display_order, show_in_menu, show_on_homepage, homepage_priority) VALUES
        ('Politics', 'politics', 'Political news and analysis', '#dc3545', 1, true, true, 1),
        ('Technology', 'technology', 'Latest tech trends and innovations', '#007bff', 2, true, true, 2),
        ('Sports', 'sports', 'Sports news and updates', '#28a745', 3, true, true, 3),
        ('Business', 'business', 'Business and economic news', '#fd7e14', 4, true, true, 4),
        ('Entertainment', 'entertainment', 'Entertainment and celebrity news', '#6f42c1', 5, true, false, 5)
        ON CONFLICT (slug) DO NOTHING;
        ");

        // Insert subcategories
        $conn->exec("
        INSERT INTO categories (name, slug, description, color, display_order, parent_category_id, is_subcategory, show_in_menu, show_on_homepage) VALUES
        ('World Politics', 'world-politics', 'International political news', '#dc3545', 1, 1, true, true, false),
        ('Local Politics', 'local-politics', 'Local and regional political coverage', '#dc3545', 2, 1, true, true, false),
        ('Artificial Intelligence', 'artificial-intelligence', 'AI and machine learning news', '#007bff', 1, 2, true, true, true),
        ('Mobile Technology', 'mobile-technology', 'Smartphones and mobile tech', '#007bff', 2, 2, true, true, false),
        ('Football', 'football', 'Football news and updates', '#28a745', 1, 3, true, true, true),
        ('Basketball', 'basketball', 'Basketball coverage', '#28a745', 2, 3, true, true, false)
        ON CONFLICT (slug) DO NOTHING;
        ");

        // Insert homepage sections
        $conn->exec("
        INSERT INTO homepage_sections (section_name, section_title, layout_style, article_limit, is_enabled, display_order) VALUES
        ('breaking', 'Breaking News', 'banner', 3, true, 1),
        ('featured', 'Featured Articles', 'grid', 6, true, 2),
        ('latest', 'Latest News', 'list', 8, true, 3),
        ('trending', 'Trending Now', 'grid', 4, true, 4)
        ON CONFLICT DO NOTHING;
        ");

        // Insert default category display settings
        $conn->exec("
        INSERT INTO category_display_settings (category_id, homepage_visible, display_order, layout_type, max_articles)
        SELECT id, true, id, 'grid', 6 FROM categories
        ON CONFLICT DO NOTHING;
        ");

        // Insert sample articles
        $this->insertSampleArticles($conn);
    }

    private function insertSampleArticles($conn) {
        $sampleArticles = [
            // Politics articles
            ['Breaking: Major Political Development Unfolds', 'breaking-major-political-development-unfolds', 'A significant political event has captured national attention today...', 'This is a comprehensive article about the latest political development that has everyone talking. The content would go here with detailed analysis and reporting.', 1, 2, 'published', true, true, 1250],
            ['Election Campaign Updates', 'election-campaign-updates', 'Latest updates from the campaign trail...', 'Campaign coverage with all the latest developments from candidates across the political spectrum.', 1, 1, 'published', false, false, 890],
            ['Policy Analysis: New Healthcare Bill', 'policy-analysis-new-healthcare-bill', 'Breaking down the implications of proposed healthcare legislation...', 'In-depth analysis of the new healthcare bill and its potential impact on citizens.', 1, 2, 'published', true, false, 650],
            
            // Technology articles  
            ['Revolutionary AI Technology Announced', 'revolutionary-ai-technology-announced', 'Tech giant reveals groundbreaking AI innovation...', 'The technology industry is buzzing with news of this revolutionary AI advancement.', 2, 3, 'published', true, false, 890],
            ['Smartphone Innovation Breakthrough', 'smartphone-innovation-breakthrough', 'Latest mobile technology promises enhanced user experience...', 'New smartphone features that are changing how we interact with technology.', 2, 1, 'published', false, false, 520],
            ['Cybersecurity Alert: New Threats Emerge', 'cybersecurity-alert-new-threats-emerge', 'Security experts warn of emerging digital threats...', 'Critical cybersecurity update covering the latest threats and protection measures.', 2, 2, 'published', true, true, 430],
            
            // Sports articles
            ['Championship Game Results', 'championship-game-results', 'Thrilling match concludes with unexpected victory...', 'Sports fans witnessed an incredible game last night.', 3, 1, 'published', false, false, 650],
            ['Olympic Preparations Underway', 'olympic-preparations-underway', 'Athletes gear up for upcoming Olympic games...', 'Comprehensive coverage of Olympic preparations and athlete profiles.', 3, 3, 'published', true, false, 380],
            ['Transfer News: Major Player Moves', 'transfer-news-major-player-moves', 'Significant player transfers shake up the sports world...', 'Latest transfer news and analysis of how it affects team dynamics.', 3, 2, 'published', false, false, 290],
            
            // Business articles
            ['Market Analysis: Economy Shows Strong Growth', 'market-analysis-economy-shows-strong-growth', 'Latest economic indicators point to positive trends...', 'Financial experts are optimistic about recent market developments.', 4, 2, 'published', true, false, 420],
            ['Startup Funding Reaches New Heights', 'startup-funding-reaches-new-heights', 'Venture capital investment hits record levels...', 'Analysis of the startup ecosystem and record-breaking funding rounds.', 4, 1, 'published', false, false, 340],
            
            // More Politics articles (total 10)
            ['Legislative Update: New Bills Introduced', 'legislative-update-new-bills-introduced', 'Congress introduces several new pieces of legislation this week...', 'Detailed coverage of the new legislative proposals and their potential impact on various sectors.', 1, 1, 'published', false, false, 380],
            ['International Relations: Trade Agreement Talks', 'international-relations-trade-agreement-talks', 'Diplomatic efforts continue on new international trade agreements...', 'Analysis of ongoing trade negotiations and their implications for the global economy.', 1, 2, 'published', true, false, 510],
            ['Political Commentary: Weekly Analysis', 'political-commentary-weekly-analysis', 'Expert analysis of this weeks political developments...', 'In-depth political commentary covering the weeks most significant events.', 1, 3, 'published', false, false, 290],
            ['Government Policy: Education Reform Proposal', 'government-policy-education-reform-proposal', 'New education policy proposals aim to improve student outcomes...', 'Comprehensive look at proposed education reforms and their potential benefits.', 1, 1, 'published', false, false, 420],
            ['Local Elections: Candidate Profiles', 'local-elections-candidate-profiles', 'Meet the candidates running in upcoming local elections...', 'Detailed profiles of local election candidates and their policy positions.', 1, 2, 'published', false, false, 340],
            ['Budget Debate: Spending Priorities Discussed', 'budget-debate-spending-priorities-discussed', 'Lawmakers debate government spending priorities for next fiscal year...', 'Analysis of budget proposals and the debate over spending priorities.', 1, 3, 'published', true, false, 490],
            ['Voting Rights: New Legislation Proposed', 'voting-rights-new-legislation-proposed', 'Proposed legislation aims to expand voting access and security...', 'Examination of new voting rights legislation and its potential impact.', 1, 1, 'published', false, false, 370],
            
            // More Technology articles (total 10)
            ['Cloud Computing: Enterprise Solutions', 'cloud-computing-enterprise-solutions', 'New cloud computing solutions revolutionize enterprise operations...', 'How modern cloud technologies are transforming business operations worldwide.', 2, 2, 'published', false, false, 460],
            ['Quantum Computing Breakthrough', 'quantum-computing-breakthrough', 'Scientists achieve new milestone in quantum computing research...', 'Latest developments in quantum computing and their potential applications.', 2, 3, 'published', true, false, 590],
            ['Tech Industry: Startup Acquisitions', 'tech-industry-startup-acquisitions', 'Major tech companies acquire promising startups this quarter...', 'Analysis of recent tech acquisitions and their strategic implications.', 2, 1, 'published', false, false, 320],
            ['Data Privacy: New Regulations Implemented', 'data-privacy-new-regulations-implemented', 'New data privacy regulations take effect globally...', 'Understanding the impact of new privacy laws on technology companies.', 2, 2, 'published', false, false, 410],
            ['Electric Vehicles: Technology Advances', 'electric-vehicles-technology-advances', 'Latest advances in electric vehicle technology and infrastructure...', 'Comprehensive coverage of EV technology improvements and market trends.', 2, 3, 'published', true, false, 520],
            ['Software Development: New Programming Languages', 'software-development-new-programming-languages', 'Emerging programming languages gain popularity among developers...', 'Overview of new programming languages and their growing adoption rates.', 2, 1, 'published', false, false, 380],
            ['Blockchain Technology: Real-world Applications', 'blockchain-technology-real-world-applications', 'Blockchain finds new applications beyond cryptocurrency...', 'Exploring practical uses of blockchain technology across industries.', 2, 2, 'published', false, false, 450],
            
            // More Sports articles (total 10)
            ['Tennis Tournament: Finals Results', 'tennis-tournament-finals-results', 'Exciting tennis tournament concludes with thrilling final matches...', 'Complete coverage of the tennis tournament finals and player performances.', 3, 3, 'published', false, false, 390],
            ['Baseball Season: Playoff Predictions', 'baseball-season-playoff-predictions', 'Analysts predict which teams will make it to the playoffs...', 'Expert analysis and predictions for the upcoming baseball playoffs.', 3, 1, 'published', true, false, 480],
            ['Soccer World Cup: Team Preparations', 'soccer-world-cup-team-preparations', 'National teams prepare for upcoming World Cup competition...', 'Behind-the-scenes look at World Cup preparations and team strategies.', 3, 2, 'published', false, false, 350],
            ['Winter Olympics: Medal Predictions', 'winter-olympics-medal-predictions', 'Sports analysts predict medal winners for upcoming Winter Olympics...', 'Comprehensive preview of Winter Olympics events and medal contenders.', 3, 3, 'published', false, false, 420],
            ['Golf Championship: Tournament Highlights', 'golf-championship-tournament-highlights', 'Professional golf championship delivers exciting competition...', 'Highlights and analysis from the recent professional golf championship.', 3, 1, 'published', false, false, 310],
            ['Basketball Draft: Top Prospects Analyzed', 'basketball-draft-top-prospects-analyzed', 'Basketball scouts analyze top prospects for upcoming draft...', 'Detailed analysis of basketball draft prospects and team needs.', 3, 2, 'published', true, false, 440],
            ['Swimming Records: New World Records Set', 'swimming-records-new-world-records-set', 'International swimming competition sees multiple world records broken...', 'Coverage of record-breaking performances at international swimming events.', 3, 3, 'published', false, false, 360],
            
            // More Business articles (total 10)
            ['Stock Market: Weekly Performance Review', 'stock-market-weekly-performance-review', 'Financial markets show mixed performance this week...', 'Analysis of weekly stock market performance and economic indicators.', 4, 1, 'published', false, false, 390],
            ['Corporate Earnings: Quarterly Reports Released', 'corporate-earnings-quarterly-reports-released', 'Major corporations release quarterly earnings reports...', 'Summary and analysis of quarterly earnings from major corporations.', 4, 2, 'published', true, false, 470],
            ['Real Estate Market: Housing Trends', 'real-estate-market-housing-trends', 'Real estate market shows new trends in housing demand...', 'Analysis of current real estate trends and market predictions.', 4, 3, 'published', false, false, 350],
            ['Banking Sector: Interest Rate Changes', 'banking-sector-interest-rate-changes', 'Central bank announces changes to interest rates...', 'Impact analysis of interest rate changes on the banking sector.', 4, 1, 'published', false, false, 410],
            ['Retail Industry: Holiday Sales Projections', 'retail-industry-holiday-sales-projections', 'Retailers prepare for holiday shopping season with optimistic projections...', 'Analysis of holiday sales projections and retail industry trends.', 4, 2, 'published', false, false, 320],
            ['Energy Sector: Renewable Energy Investments', 'energy-sector-renewable-energy-investments', 'Major investments in renewable energy projects announced...', 'Coverage of new renewable energy investments and their market impact.', 4, 3, 'published', true, false, 490],
            ['Manufacturing Industry: Supply Chain Updates', 'manufacturing-industry-supply-chain-updates', 'Manufacturing sector adapts to global supply chain challenges...', 'Analysis of supply chain improvements and manufacturing trends.', 4, 1, 'published', false, false, 370],
            ['Cryptocurrency Market: Digital Asset Trends', 'cryptocurrency-market-digital-asset-trends', 'Cryptocurrency markets show new trends in digital asset adoption...', 'Overview of cryptocurrency market trends and regulatory developments.', 4, 2, 'published', false, false, 430],
            
            // More Entertainment articles (total 10)
            ['Celebrity News Update', 'celebrity-news-update', 'Entertainment industry buzz around latest events...', 'The entertainment world has been active with several noteworthy events.', 5, 3, 'published', false, false, 330],
            ['Movie Industry Box Office Records', 'movie-industry-box-office-records', 'Latest film releases break box office records...', 'Analysis of recent box office successes and what they mean for the industry.', 5, 1, 'published', true, false, 280],
            ['Music Industry: Album Release Reviews', 'music-industry-album-release-reviews', 'New album releases receive critical acclaim and commercial success...', 'Reviews and analysis of the latest album releases from popular artists.', 5, 2, 'published', false, false, 390],
            ['Television Shows: Streaming Platform Updates', 'television-shows-streaming-platform-updates', 'Streaming platforms announce new original series and content...', 'Overview of new content coming to major streaming platforms.', 5, 3, 'published', false, false, 350],
            ['Award Shows: Red Carpet Fashion Highlights', 'award-shows-red-carpet-fashion-highlights', 'Fashion takes center stage at major entertainment award shows...', 'Coverage of fashion trends and highlights from recent award ceremonies.', 5, 1, 'published', false, false, 310],
            ['Gaming Industry: New Video Game Releases', 'gaming-industry-new-video-game-releases', 'Highly anticipated video games launch to enthusiastic reception...', 'Reviews and coverage of the latest video game releases and industry trends.', 5, 2, 'published', true, false, 420],
            ['Theater Productions: Broadway Season Preview', 'theater-productions-broadway-season-preview', 'Broadway prepares for new season with exciting theatrical productions...', 'Preview of upcoming Broadway shows and theatrical productions.', 5, 3, 'published', false, false, 370],
            ['Book Publishing: Bestseller Lists Update', 'book-publishing-bestseller-lists-update', 'New books climb bestseller lists across multiple genres...', 'Analysis of current bestseller trends and notable new book releases.', 5, 1, 'published', false, false, 290]
        ];

        $stmt = $conn->prepare(
            "INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, status, is_featured, is_breaking, views, featured_image, published_at) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP - (? || ' hours')::interval) " .
            "ON CONFLICT (slug) DO NOTHING"
        );

        foreach ($sampleArticles as $index => $article) {
            $hoursAgo = 2 + ($index * 2);
            $stmt->execute([
                $article[0], // title
                $article[1], // slug
                $article[2], // excerpt
                $article[3], // content
                $article[4], // category_id
                $article[5], // author_id
                $article[6], // status
                $article[7] ? 't' : 'f', // is_featured
                $article[8] ? 't' : 'f', // is_breaking
                $article[9], // views
                'https://images.pexels.com/photos/28468503/pexels-photo-28468503.jpeg', // featured_image
                $hoursAgo // hours ago
            ]);
        }
    }
}
?>