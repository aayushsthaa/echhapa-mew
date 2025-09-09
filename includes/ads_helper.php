<?php
// Advertisement Helper Functions

function getAdsByPosition($position = 'sidebar', $limit = 1) {
    global $conn;
    if (!$conn) {
        $db = new Database();
        $conn = $db->getConnection();
    }
    
    $stmt = $conn->prepare("
        SELECT * FROM ads 
        WHERE position = ? AND status = 'active' 
        AND (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY RANDOM() 
        LIMIT ?
    ");
    $stmt->execute([$position, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function displayAd($ad, $track_impression = true) {
    if (!$ad) return '';
    
    // Track impression if enabled
    if ($track_impression) {
        trackAdImpression($ad['id']);
    }
    
    $html = '<div class="advertisement-wrapper" data-ad-id="' . $ad['id'] . '">';
    $html .= '<div class="ad-label">Advertisement</div>';
    
    if ($ad['type'] === 'banner' && $ad['image_url']) {
        $html .= '<a href="' . htmlspecialchars($ad['click_url']) . '" target="_blank" onclick="trackAdClick(' . $ad['id'] . ')" class="ad-banner">';
        $html .= '<img src="' . htmlspecialchars($ad['image_url']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="img-fluid">';
        $html .= '</a>';
    } elseif ($ad['type'] === 'text') {
        $html .= '<div class="ad-text">';
        $html .= '<h6>' . htmlspecialchars($ad['title']) . '</h6>';
        $html .= '<p>' . nl2br(htmlspecialchars($ad['description'])) . '</p>';
        if ($ad['click_url']) {
            $html .= '<a href="' . htmlspecialchars($ad['click_url']) . '" target="_blank" onclick="trackAdClick(' . $ad['id'] . ')" class="btn btn-primary btn-sm">Learn More</a>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

function trackAdImpression($ad_id) {
    global $conn;
    if (!$conn) {
        $db = new Database();
        $conn = $db->getConnection();
    }
    
    $stmt = $conn->prepare("INSERT INTO ad_impressions (ad_id, user_ip, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([
        $ad_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

function trackAdClick($ad_id) {
    global $conn;
    if (!$conn) {
        $db = new Database();
        $conn = $db->getConnection();
    }
    
    $stmt = $conn->prepare("INSERT INTO ad_clicks (ad_id, user_ip, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([
        $ad_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

function getSidebarAds() {
    return getAdsByPosition('sidebar', 2);
}

function getHeaderAds() {
    return getAdsByPosition('header', 1);
}

function getInlineAds() {
    return getAdsByPosition('inline', 1);
}

function getBetweenCategoriesAds() {
    return getAdsByPosition('between-categories', 1);
}
?>