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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            parent_id INTEGER REFERENCES categories(id) ON DELETE CASCADE,
            level INTEGER DEFAULT 0
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

        -- Comments table removed as requested

        -- Create homepage_sections table
        CREATE TABLE IF NOT EXISTS homepage_sections (
            id SERIAL PRIMARY KEY,
            section_name VARCHAR(50) NOT NULL,
            section_title VARCHAR(100) NOT NULL,
            layout_style VARCHAR(20) DEFAULT 'grid' CHECK (layout_style IN ('banner', 'grid', 'list', 'carousel', 'cards', 'featured', 'magazine', 'minimal')),
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
            category_id INTEGER UNIQUE REFERENCES categories(id) ON DELETE CASCADE,
            show_on_homepage BOOLEAN DEFAULT true,
            display_order INTEGER DEFAULT 0,
            layout_type VARCHAR(50) DEFAULT 'grid',
            layout_style VARCHAR(50) DEFAULT 'grid',
            max_articles INTEGER DEFAULT 6,
            articles_limit INTEGER DEFAULT 6,
            show_excerpts BOOLEAN DEFAULT true,
            show_images BOOLEAN DEFAULT true,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create ads table
        CREATE TABLE IF NOT EXISTS ads (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(500),
            click_url VARCHAR(500) NOT NULL,
            ad_type VARCHAR(50) DEFAULT 'banner' CHECK (ad_type IN ('banner', 'sidebar', 'inline', 'popup')),
            position VARCHAR(100) DEFAULT 'header',
            status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'expired')),
            start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            end_date TIMESTAMP,
            budget DECIMAL(10,2) DEFAULT 0.00,
            cost_per_click DECIMAL(8,2) DEFAULT 0.00,
            created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Create ad tracking tables
        CREATE TABLE IF NOT EXISTS ad_impressions (
            id SERIAL PRIMARY KEY,
            ad_id INTEGER REFERENCES ads(id) ON DELETE CASCADE,
            user_ip VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS ad_clicks (
            id SERIAL PRIMARY KEY,
            ad_id INTEGER REFERENCES ads(id) ON DELETE CASCADE,
            user_ip VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
        INSERT INTO category_display_settings (category_id, show_on_homepage, display_order, layout_type, layout_style, max_articles, articles_limit, show_excerpts, show_images)
        SELECT id, true, id, 'grid', 'grid', 6, 6, true, true FROM categories
        ON CONFLICT DO NOTHING;
        ");

        // Insert sample articles
        $this->insertSampleArticles($conn);
    }

    private function insertSampleArticles($conn) {
        $detailedArticles = [
            // Politics Articles (2)
            [
                'Breaking: Historic Climate Summit Reaches Unprecedented Agreement',
                'historic-climate-summit-unprecedented-agreement',
                'World leaders from 195 nations have reached a groundbreaking consensus on climate action, marking the most significant environmental accord in decades.',
                '<p>In a historic moment that will be remembered for generations, world leaders from 195 nations have successfully concluded the most ambitious climate summit in history, reaching an unprecedented agreement that sets binding targets for carbon reduction and establishes a revolutionary global fund for climate adaptation.</p>

                <p>The marathon negotiations, which extended three days beyond the original schedule, saw intense discussions between developed and developing nations. The final agreement includes a commitment to reduce global greenhouse gas emissions by 65% by 2035, with developed nations pledging an additional $500 billion annually to support climate initiatives in developing countries.</p>

                <p>"This is not just another climate agreement – this is a covenant with our children and their children," declared United Nations Secretary-General Maria Santos during the closing ceremony. "For the first time, we have binding commitments with real enforcement mechanisms."</p>

                <p>The accord introduces several groundbreaking elements that distinguish it from previous climate agreements. First, it establishes a mandatory carbon pricing mechanism that will be implemented across all participating nations by 2027. Second, it creates the Global Climate Adaptation Fund, which will receive funding through a small transaction tax on international financial transfers.</p>

                <p>Perhaps most significantly, the agreement includes provisions for climate refugees, officially recognizing climate displacement as a legitimate reason for seeking international protection. This represents a major shift in international law and could affect millions of people in the coming decades.</p>

                <p>Environmental advocates have cautiously welcomed the agreement while emphasizing the importance of implementation. "The words on paper are encouraging, but the real test will come in the months and years ahead as nations work to translate these commitments into concrete action," said Dr. Elena Rodriguez, director of the International Climate Policy Institute.</p>

                <p>The business community has also responded positively, with many major corporations announcing accelerated timelines for their own carbon neutrality goals. Tech giant GlobalTech announced it would move its carbon neutrality target from 2040 to 2030, while renewable energy companies saw their stock prices surge in response to the news.</p>

                <p>Critics, however, point out that previous climate agreements have fallen short of their targets, and question whether the enforcement mechanisms included in this accord will be sufficient to ensure compliance. Some developing nations have expressed concerns about the economic impact of rapid decarbonization on their growing economies.</p>

                <p>The next major milestone will come in six months when participating nations must submit their detailed implementation plans to the newly established Global Climate Monitoring Authority, an independent body tasked with tracking progress and ensuring accountability.</p>',
                1, 2, 'published', true, true, 1850
            ],
            
            [
                'Healthcare Reform: Revolutionary Digital Medicine Initiative Launches Nationwide',
                'healthcare-reform-digital-medicine-nationwide',
                'A comprehensive digital health transformation program promises to revolutionize patient care through AI-powered diagnostics, telemedicine expansion, and integrated health records.',
                '<p>The national healthcare system is undergoing its most significant transformation in decades with the launch of the Digital Medicine Initiative, a comprehensive program that leverages cutting-edge technology to improve patient outcomes, reduce costs, and increase access to quality care across the country.</p>

                <p>The initiative, developed through a collaboration between the Department of Health and leading technology companies, introduces AI-powered diagnostic tools, expands telemedicine capabilities, and creates a unified electronic health record system that will connect hospitals, clinics, and private practices nationwide.</p>

                <p>Dr. Michael Chen, the program\'s chief medical officer, explained that the initiative addresses three critical challenges in modern healthcare: accessibility, efficiency, and quality of care. "We\'re not just digitizing existing processes," he said. "We\'re fundamentally reimagining how healthcare is delivered in the 21st century."</p>

                <p>The AI diagnostic component represents perhaps the most ambitious aspect of the program. Machine learning algorithms trained on millions of medical images and patient records can now assist physicians in diagnosing conditions ranging from skin cancer to heart disease with accuracy rates that exceed traditional methods.</p>

                <p>Early pilot programs in five major metropolitan areas have shown remarkable results. Emergency room wait times have decreased by an average of 40%, while diagnostic accuracy has improved by 23%. Perhaps most importantly, patient satisfaction scores have reached all-time highs in participating facilities.</p>

                <p>The telemedicine expansion is particularly significant for rural communities that have historically faced challenges accessing specialist care. Under the new program, patients in remote areas can consult with specialists hundreds of miles away through high-definition video consultations supported by advanced diagnostic equipment.</p>

                <p>Privacy advocates have raised concerns about the security of the centralized health records system, prompting the implementation of advanced encryption protocols and strict access controls. The system uses blockchain technology to ensure data integrity while maintaining patient privacy through sophisticated anonymization techniques.</p>

                <p>The economic implications of the initiative are substantial. Healthcare economists project that the program could reduce national healthcare spending by up to 15% over the next decade while improving patient outcomes across all demographic groups.</p>

                <p>Training programs for healthcare workers are already underway, with over 50,000 medical professionals participating in certification courses for the new digital tools. The transition is expected to be completed within three years, with full nationwide implementation targeted for 2028.</p>',
                1, 1, 'published', true, false, 1420
            ],

            // Technology Articles (3)
            [
                'Quantum Computing Breakthrough: IBM Unveils 1000-Qubit Processor Revolution',
                'quantum-computing-breakthrough-ibm-1000-qubit-processor',
                'IBM\'s latest quantum processor represents a massive leap forward in computational power, promising to solve complex problems previously thought impossible.',
                '<p>IBM has achieved a monumental breakthrough in quantum computing with the unveiling of their revolutionary 1000-qubit processor, codenamed "Quantum Horizon." This achievement represents a quantum leap in computational capability, potentially solving complex problems in cryptography, drug discovery, and climate modeling that would take classical computers millions of years to process.</p>

                <p>The announcement sent shockwaves through the technology industry, with competitors scrambling to understand the implications of this advancement. Unlike previous quantum systems that required extremely controlled environments, IBM\'s new processor operates at higher temperatures and with greater stability, making it more practical for commercial applications.</p>

                <p>"We\'re not just talking about incremental improvements anymore," explained Dr. Sarah Williams, IBM\'s Director of Quantum Research. "This is a paradigm shift that brings us significantly closer to quantum advantage in real-world applications."</p>

                <p>The processor utilizes a novel approach to quantum error correction, dramatically reducing the interference that has plagued quantum systems for decades. This breakthrough was achieved through the development of new superconducting materials and innovative qubit design that maintains quantum coherence for unprecedented durations.</p>

                <p>Initial testing has demonstrated the processor\'s ability to solve optimization problems that are crucial for logistics, financial modeling, and artificial intelligence. Major corporations including Goldman Sachs, Mercedes-Benz, and pharmaceutical giant Roche have already signed agreements to access the quantum system for specific research projects.</p>

                <p>The implications for cybersecurity are particularly significant, as quantum computers of this caliber could potentially break current encryption methods. However, IBM has simultaneously announced the development of quantum-resistant cryptography protocols to address these security concerns.</p>

                <p>In the pharmaceutical industry, researchers are excited about the potential for quantum-accelerated drug discovery. Complex molecular interactions that currently require years of study could be modeled in weeks, potentially accelerating the development of new medications for diseases like Alzheimer\'s and cancer.</p>

                <p>Climate scientists are equally enthusiastic, as the processor\'s computational power could enable more accurate climate models, helping predict weather patterns and climate change effects with unprecedented precision.</p>

                <p>The technology is not yet available for general commercial use, but IBM plans to make it accessible through their quantum cloud service by late 2025. Universities and research institutions will have priority access to support ongoing quantum research initiatives.</p>

                <p>Industry analysts predict that this breakthrough could accelerate the timeline for practical quantum computing applications by several years, with potential economic impacts measured in trillions of dollars as quantum solutions transform industries ranging from finance to pharmaceuticals.</p>',
                2, 3, 'published', true, false, 2100
            ],

            [
                'Artificial Intelligence Revolution: New Language Model Achieves Human-Level Reasoning',
                'ai-revolution-language-model-human-level-reasoning',
                'Scientists have developed an AI system that demonstrates human-level reasoning capabilities across multiple domains, marking a significant milestone in artificial general intelligence.',
                '<p>Researchers at the Advanced AI Institute have achieved a breakthrough that many experts believed was still years away: the development of an artificial intelligence system that demonstrates human-level reasoning capabilities across diverse domains, from scientific research to creative problem-solving.</p>

                <p>The system, called "ReasonAI," represents a fundamental advance in artificial general intelligence (AGI), moving beyond the narrow AI applications that have dominated the field. Unlike previous AI models that excel in specific tasks, ReasonAI demonstrates the ability to understand, learn, and apply knowledge across multiple disciplines with human-like flexibility.</p>

                <p>Dr. Elena Kozlov, the project\'s lead researcher, described the breakthrough as "the culmination of decades of research into cognitive architectures and neural network design." The system combines advanced transformer architectures with novel reasoning algorithms that mirror human cognitive processes.</p>

                <p>In comprehensive testing, ReasonAI has demonstrated remarkable capabilities. It can solve complex mathematical proofs, generate original scientific hypotheses, create coherent literary works, and engage in sophisticated philosophical discussions. Most impressively, it can transfer knowledge learned in one domain to solve problems in completely different fields.</p>

                <p>The system\'s reasoning capabilities were tested against human experts in various fields. In scientific reasoning tasks, ReasonAI matched or exceeded the performance of PhD-level researchers. In creative tasks, it produced work that was consistently rated by human judges as original and meaningful.</p>

                <p>What sets ReasonAI apart from previous AI systems is its ability to understand causality, engage in counterfactual thinking, and demonstrate what researchers call "common sense reasoning." It can understand not just what happens, but why it happens and what might happen under different circumstances.</p>

                <p>The implications for scientific research are profound. ReasonAI has already contributed to breakthrough discoveries in materials science and has proposed novel approaches to cancer treatment that are now being investigated by research teams around the world.</p>

                <p>In education, the system is being tested as a personalized tutor that can adapt its teaching methods to individual learning styles. Early results suggest that students working with ReasonAI show significantly improved learning outcomes compared to traditional instruction methods.</p>

                <p>However, the development has also raised important ethical questions about the future of human work and decision-making. Philosophers and ethicists are grappling with questions about AI consciousness and the rights that such systems might possess.</p>

                <p>The research team has implemented strict safety protocols and has committed to open publication of their safety research. They are working closely with policymakers to develop appropriate governance frameworks for AGI systems.</p>',
                2, 1, 'published', false, false, 1680
            ],

            [
                'Renewable Energy Milestone: Solar Power Efficiency Reaches 47% in Laboratory Breakthrough',
                'renewable-energy-solar-power-efficiency-47-percent',
                'Scientists achieve record-breaking solar panel efficiency using innovative perovskite-silicon tandem cells, potentially revolutionizing clean energy adoption worldwide.',
                '<p>Scientists at the National Renewable Energy Laboratory have achieved a groundbreaking milestone in solar technology, developing perovskite-silicon tandem solar cells that have reached an unprecedented 47% efficiency rate in laboratory conditions, nearly doubling the efficiency of conventional solar panels currently available in the market.</p>

                <p>This breakthrough represents a quantum leap in renewable energy technology, potentially transforming the global energy landscape by making solar power competitive with fossil fuels even in regions with limited sunlight. The achievement comes at a critical time when the world is urgently seeking solutions to combat climate change and reduce dependence on carbon-intensive energy sources.</p>

                <p>Dr. Maria Rodriguez, who led the research team, explained that the breakthrough was achieved by perfecting the combination of perovskite and silicon materials in a tandem cell configuration. "We\'ve solved the stability issues that have plagued perovskite cells for years while maintaining their exceptional light-absorption properties," she said.</p>

                <p>Traditional silicon solar panels typically achieve efficiencies of 20-22% in real-world conditions, while the best commercial panels reach about 26%. The new tandem cells capture light across a broader spectrum, with the perovskite layer absorbing high-energy photons and the silicon layer capturing lower-energy light that passes through.</p>

                <p>The research team overcame several technical challenges that have limited perovskite cell applications. They developed new encapsulation techniques that protect the perovskite material from moisture and oxygen, addressing the degradation issues that previously made these cells impractical for long-term use.</p>

                <p>Industry analysts predict that if successfully commercialized, this technology could reduce the cost of solar installations by up to 40% while dramatically increasing power output. This could accelerate solar adoption in regions where space for solar installations is limited, such as densely populated urban areas.</p>

                <p>Major solar manufacturers have already expressed interest in licensing the technology. SunPower Corporation announced plans to invest $500 million in developing commercial production methods for the new cells, with the goal of bringing them to market within five years.</p>

                <p>The implications extend beyond residential and commercial solar installations. The high efficiency of these cells makes them ideal for space applications, electric vehicle integration, and portable electronics. NASA has announced plans to test the technology for future Mars missions.</p>

                <p>Environmental scientists calculate that widespread adoption of this technology could significantly accelerate the transition to renewable energy. If deployed globally, it could enable solar power to meet 40% of world electricity demand by 2035, compared to current projections of 25%.</p>

                <p>The next phase of research focuses on scaling up production while maintaining efficiency and developing cost-effective manufacturing processes. The team is also working on flexible versions of the cells that could be integrated into building materials and wearable devices.</p>',
                2, 2, 'published', true, false, 1950
            ],

            // Sports Articles (2)
            [
                'World Cup Final: Historic Match Delivers Unprecedented Global Viewership and Drama',
                'world-cup-final-historic-match-unprecedented-viewership',
                'The most-watched sporting event in history culminates with a thrilling final that saw dramatic twists, record-breaking performances, and emotional moments that captivated billions.',
                '<p>In what has been declared the greatest World Cup final in history, over 2.5 billion people worldwide witnessed a spectacular match that combined athletic excellence, dramatic storylines, and emotional moments that will be remembered for generations. The final, which required extra time and a penalty shootout, delivered on every promise of excitement and drama.</p>

                <p>The match began with an explosive opening as both teams demonstrated the attacking football that had characterized their journey to the final. Within the first 20 minutes, spectacular goals from both sides set the tone for what would become an end-to-end thriller that kept viewers on the edge of their seats.</p>

                <p>Captain Marco Silva\'s performance in the final was nothing short of legendary. At 34 years old, playing in what he announced would be his final international tournament, Silva scored twice and provided an assist that will be studied by football tacticians for years to come. His leadership on the field was matched by his emotional speech to teammates during the halftime break.</p>

                <p>The match\'s defining moment came in the 89th minute when a controversial penalty decision sparked intense debate among fans and pundits. The Video Assistant Referee review took nearly five minutes, during which the tension in the stadium was palpable. The eventual decision to award the penalty changed the trajectory of the match and led to the dramatic extra time period.</p>

                <p>Beyond the action on the field, the final showcased the global power of football to unite people across cultural and geographical boundaries. Watch parties were organized in every continent, with some cities reporting that normal traffic patterns ceased as people gathered to watch the match together.</p>

                <p>The economic impact of the tournament has been unprecedented. Host cities reported tourism increases of over 300% during the tournament period, while global television advertising revenue exceeded $15 billion. The final match alone generated an estimated $2.8 billion in global economic activity.</p>

                <p>Technology played a crucial role in the final, with advanced analytics providing insights into player performance and tactical decisions. The implementation of semi-automated offside detection ensured that controversial decisions were minimized, though debate continues about the penalty decision that shaped the match\'s outcome.</p>

                <p>For many players, this final represented the culmination of lifelong dreams. Several players overcame significant personal challenges to reach this stage, including goalkeeper Antonio Martinez, who dedicated his performance to his late father, and midfielder Lisa Chen, who became the youngest player ever to score in a World Cup final.</p>

                <p>The celebration that followed the final whistle was witnessed by fans around the world, with spontaneous street parties erupting in major cities. Social media platforms reported record engagement levels, with over 100 million posts related to the match in the 24 hours following the final whistle.</p>

                <p>As the football world begins to process this historic final, attention already turns to the next tournament cycle. The performance standards set in this World Cup have raised expectations for future competitions, with several innovations in player preparation and tournament organization likely to become standard practices.</p>',
                3, 1, 'published', false, false, 1830
            ],

            [
                'Olympic Records Shattered: Swimming Championships Witness Unprecedented Athletic Achievements',
                'olympic-records-swimming-championships-unprecedented-achievements',
                'The swimming competitions have delivered historic performances with multiple world records broken and new standards set for aquatic sports excellence.',
                '<p>The swimming competitions at this year\'s championships have redefined the boundaries of human athletic achievement, with swimmers from around the world shattering records that stood for decades and setting new benchmarks for excellence in aquatic sports. Over the course of eight days, spectators witnessed history being made in the pool.</p>

                <p>The standout performer has been 19-year-old phenom Sarah Mitchell from Australia, who broke three individual world records and contributed to a relay record that many experts believed would stand for years. Her performance in the 200-meter butterfly was particularly stunning, improving the previous record by nearly two seconds in a display of technical perfection and raw speed.</p>

                <p>Mitchell\'s journey to these championships has been remarkable, overcoming a shoulder injury that threatened to end her career just 18 months ago. Her dedication to rehabilitation and technique refinement has paid dividends, as evidenced by her flawless stroke mechanics that have been praised by swimming coaches worldwide.</p>

                <p>The men\'s events have been equally impressive, with veteran swimmer Carlos Rodriguez of Spain achieving what many considered impossible: winning gold medals in both sprint and distance events at the age of 29. His victory in the 1500-meter freestyle, followed three days later by a triumph in the 50-meter sprint, demonstrated versatility rarely seen in elite swimming.</p>

                <p>Technology has played a significant role in these record-breaking performances. Advanced pool design, improved lane rope technology, and sophisticated timing systems have created optimal conditions for fast swimming. However, experts emphasize that the primary factor remains the exceptional training and natural talent of the athletes.</p>

                <p>The relay events have provided some of the most exciting moments of the competition. The mixed medley relay final saw four different world records broken in preliminary heats, with the final race producing times that would have won gold medals in previous championships by substantial margins.</p>

                <p>Beyond individual performances, these championships have showcased the global growth of swimming as a sport. Athletes from 15 different countries have won medals, representing the most diverse medal distribution in the history of the sport. Swimmers from emerging aquatic nations have achieved breakthrough performances that signal a bright future for the sport\'s development.</p>

                <p>The role of sports science in these achievements cannot be understated. Advanced training methods, nutrition protocols, and recovery techniques have enabled athletes to train at higher intensities while reducing injury risk. Several swimmers have credited new altitude training methods and underwater treadmill work for their improved performances.</p>

                <p>Environmental considerations have also been highlighted during the championships, with the swimming venues utilizing solar power and advanced water filtration systems that demonstrate how major sporting events can operate sustainably while maintaining world-class standards.</p>

                <p>As the swimming competitions conclude, the sport looks toward the future with unprecedented optimism. The records set during these championships have not only pushed the boundaries of human performance but have also inspired a new generation of young swimmers who watched these historic achievements unfold.</p>',
                3, 3, 'published', true, false, 1620
            ],

            // Business Articles (2)
            [
                'Global Economic Summit: Central Banks Announce Coordinated Policy Response to Market Volatility',
                'global-economic-summit-central-banks-coordinated-policy-response',
                'Major central banks unveil synchronized monetary policies designed to stabilize markets and support economic growth amid unprecedented global financial challenges.',
                '<p>In an unprecedented display of international economic cooperation, central banks from the world\'s largest economies have announced a coordinated policy response designed to address mounting market volatility and support sustainable economic growth. The announcement, made simultaneously across multiple time zones, represents the most significant monetary policy coordination since the 2008 financial crisis.</p>

                <p>The Federal Reserve, European Central Bank, Bank of Japan, and Bank of England, along with central banks from 15 other major economies, have committed to synchronized interest rate adjustments and quantitative easing measures that economists predict will inject approximately $2.5 trillion into global financial markets over the next 18 months.</p>

                <p>Federal Reserve Chair Janet Morrison explained the rationale behind the coordinated approach during a joint press conference. "The interconnected nature of global markets requires coordinated responses to ensure financial stability and promote sustainable economic growth," she stated, emphasizing that unilateral actions by individual central banks have proven insufficient to address current market conditions.</p>

                <p>The policy package includes several innovative elements that distinguish it from previous monetary interventions. Most notably, the central banks have established a new digital currency swap network that will facilitate faster and more efficient international transactions, reducing friction in global trade and finance.</p>

                <p>Market reaction to the announcement was immediate and positive, with major stock indices gaining between 4-7% in the hours following the announcement. Bond markets also responded favorably, with yields on government securities stabilizing after weeks of volatility that had concerned investors and policymakers alike.</p>

                <p>The business community has welcomed the coordinated response, with many corporate leaders expressing relief that central banks have taken decisive action. Manufacturing companies, which have been particularly affected by supply chain disruptions and currency fluctuations, reported immediate improvements in their ability to plan medium-term investments.</p>

                <p>Small and medium-sized enterprises are expected to benefit significantly from the expanded lending programs that form part of the policy package. These programs include loan guarantees and reduced borrowing costs that should enable smaller businesses to invest in expansion and job creation.</p>

                <p>International trade is also expected to receive a substantial boost from the measures. The new currency swap arrangements will reduce transaction costs for importers and exporters, while coordinated interest rate policies should minimize the currency volatility that has disrupted trade flows in recent months.</p>

                <p>Critics, however, have raised concerns about the long-term implications of such extensive monetary intervention. Some economists warn that prolonged low interest rates and quantitative easing could lead to asset bubbles and increased income inequality, issues that central banks will need to monitor carefully as the policies are implemented.</p>

                <p>The success of this coordinated approach will be closely watched by policymakers and economists worldwide, as it could establish a new template for international economic cooperation in an increasingly interconnected global economy.</p>',
                4, 2, 'published', true, false, 1740
            ],

            [
                'Technology Sector Transformation: Major Corporations Announce $500 Billion Investment in Sustainable Innovation',
                'technology-sector-transformation-500-billion-sustainable-innovation',
                'Leading tech companies unveil massive investment commitments focused on sustainable technology development and carbon-neutral operations across their global facilities.',
                '<p>In a landmark moment for corporate environmental responsibility, a consortium of the world\'s largest technology companies has announced a collective $500 billion investment commitment over the next decade, focused on sustainable innovation and achieving carbon-neutral operations across their global infrastructure and supply chains.</p>

                <p>The announcement, made jointly by CEOs from Apple, Microsoft, Google, Amazon, and twelve other major technology firms, represents the largest corporate commitment to environmental sustainability in history. The initiative encompasses everything from renewable energy infrastructure to breakthrough technologies for carbon capture and sustainable manufacturing.</p>

                <p>Microsoft CEO Satya Nadella emphasized the transformative nature of the commitment during the announcement ceremony. "This is not just about reducing our environmental impact," he explained. "We\'re fundamentally reimagining how technology can be a force for environmental restoration and sustainable development on a global scale."</p>

                <p>The investment strategy includes several ambitious components. First, the companies will collectively build 200 new renewable energy facilities worldwide, generating enough clean electricity to power approximately 50 million homes. These facilities will utilize cutting-edge solar, wind, and energy storage technologies to ensure reliable clean power.</p>

                <p>Perhaps most significantly, the consortium has committed $150 billion to research and development of breakthrough environmental technologies. This includes advanced carbon capture systems, revolutionary battery technologies, and sustainable materials that could replace environmentally harmful components used in electronic devices.</p>

                <p>The supply chain transformation component of the initiative is equally ambitious. All participating companies have committed to working exclusively with suppliers that meet strict environmental standards by 2030. This requirement is expected to drive sustainable practices throughout the global technology manufacturing ecosystem.</p>

                <p>Innovation in data center design represents another major focus area. The companies are developing new cooling technologies, server designs, and facility management systems that could reduce the energy consumption of data centers by up to 60% while maintaining performance standards.</p>

                <p>The commitment extends to product design and lifecycle management. New sustainability standards will govern everything from device packaging to end-of-life recycling programs. Customers will benefit from more durable products designed for repairability and longer useful lives.</p>

                <p>Educational initiatives form a crucial component of the program, with the consortium establishing a $50 billion fund to support environmental technology research at universities worldwide. This investment is expected to accelerate breakthrough discoveries in areas such as renewable energy, sustainable materials, and environmental monitoring.</p>

                <p>Economic analysts predict that the initiative will create approximately 2 million new jobs globally, ranging from renewable energy technicians to environmental engineers and sustainable manufacturing specialists. Many of these positions will be in regions that have historically depended on fossil fuel industries.</p>

                <p>The environmental impact projections are equally impressive. Independent analysis suggests that full implementation of the initiative could reduce global technology sector carbon emissions by 45% within a decade, while spurring innovation that benefits industries far beyond technology.</p>',
                4, 1, 'published', false, false, 1890
            ],

            // Entertainment Articles (1)
            [
                'Entertainment Industry Revolution: Streaming Wars Reach New Heights with Unprecedented Content Investments',
                'entertainment-industry-revolution-streaming-wars-unprecedented-investments',
                'Major streaming platforms announce record-breaking content budgets and innovative technologies that are reshaping how audiences consume entertainment worldwide.',
                '<p>The global entertainment industry is experiencing its most dramatic transformation in decades as streaming platforms engage in unprecedented competition for audience attention, collectively announcing content investments exceeding $200 billion over the next three years. This massive financial commitment is reshaping creative industries and viewer habits worldwide.</p>

                <p>Netflix, Amazon Prime, Disney+, and emerging platforms have revealed ambitious content strategies that include over 5,000 new original productions ranging from blockbuster films to innovative interactive experiences. The scale of investment represents a fundamental shift in how entertainment content is conceived, produced, and distributed to global audiences.</p>

                <p>The technological innovations accompanying these investments are equally impressive. Advanced AI-powered recommendation systems now analyze viewer preferences with unprecedented sophistication, while new production technologies enable immersive experiences that blur the lines between traditional cinema and interactive media.</p>

                <p>International content has become a particular focus area, with streaming platforms investing heavily in productions from diverse markets around the world. Korean dramas, Spanish-language series, and Bollywood productions are reaching global audiences in ways that were impossible through traditional distribution channels.</p>

                <p>The impact on creative professionals has been transformative. Actors, directors, and writers are experiencing unprecedented demand for their services, with many reporting that streaming platforms offer greater creative freedom compared to traditional film and television production.</p>

                <p>Independent filmmakers are particularly benefiting from the streaming boom, as platforms actively seek diverse content to differentiate their offerings. Documentary filmmakers, in particular, are finding new opportunities to reach global audiences with specialized content that might not have found theatrical distribution.</p>

                <p>The technological infrastructure supporting this content explosion is equally impressive. Cloud-based production tools enable filmmakers to collaborate across continents, while advanced compression algorithms ensure that high-quality video can be delivered even to viewers with limited internet bandwidth.</p>

                <p>Audience viewing habits continue to evolve rapidly in response to these changes. Binge-watching has become the dominant consumption pattern, with viewers increasingly expecting entire seasons to be available simultaneously rather than following traditional weekly release schedules.</p>

                <p>The educational potential of streaming platforms is also being explored through partnerships with educational institutions and documentary producers. Several platforms have announced dedicated educational content sections designed to serve both formal learning environments and curious individual viewers.</p>

                <p>As the streaming wars intensify, traditional media companies are adapting their business models to compete in this new landscape. Many are launching their own streaming services while simultaneously licensing content to competitors, creating a complex web of partnerships and rivalries that continues to evolve.</p>',
                5, 3, 'published', false, false, 1560
            ]
        ];

        $stmt = $conn->prepare(
            "INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, status, is_featured, is_breaking, views, featured_image, published_at) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP - (? || ' hours')::interval) " .
            "ON CONFLICT (slug) DO NOTHING"
        );

        foreach ($detailedArticles as $index => $article) {
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
                'https://images.pexels.com/photos/13397143/pexels-photo-13397143.jpeg', // featured_image
                $hoursAgo // hours ago
            ]);
        }
    }
}
?>