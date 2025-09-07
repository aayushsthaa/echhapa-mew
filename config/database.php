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
            $this->port = $url['port'];
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
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
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
            
            // Entertainment articles
            ['Celebrity News Update', 'celebrity-news-update', 'Entertainment industry buzz around latest events...', 'The entertainment world has been active with several noteworthy events.', 5, 3, 'published', false, false, 330],
            ['Movie Industry Box Office Records', 'movie-industry-box-office-records', 'Latest film releases break box office records...', 'Analysis of recent box office successes and what they mean for the industry.', 5, 1, 'published', true, false, 280]
        ];

        $stmt = $conn->prepare(
            "INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, status, is_featured, is_breaking, views, published_at) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP - (? || ' hours')::interval) " .
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
                $hoursAgo // hours ago
            ]);
        }
    }
}
?>