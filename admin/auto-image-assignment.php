<?php
/**
 * Auto Image Assignment Helper
 * Automatically assigns stock images when no image is uploaded
 */

require_once '../config/config.php';

/**
 * Sanitize context text for image search
 * @param string $context
 * @return string
 */
function sanitizeForImageSearch($context) {
    // Remove HTML tags, special characters, and clean up text
    $clean = strip_tags($context);
    $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $clean);
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    
    // Limit to first 50 characters to avoid overly specific searches
    return substr($clean, 0, 50);
}

/**
 * Check if search terms contain specific keywords
 * @param string $searchTerms
 * @param array $keywords
 * @return bool
 */
function containsKeywords($searchTerms, $keywords) {
    $lowerTerms = strtolower($searchTerms);
    foreach ($keywords as $keyword) {
        if (strpos($lowerTerms, strtolower($keyword)) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Generate appropriate image description based on context and type
 * @param string $searchTerms
 * @param string $type
 * @return string
 */
function generateImageDescription($searchTerms, $type) {
    switch ($type) {
        case 'article':
            // For articles, try to create news-appropriate descriptions
            if (containsKeywords($searchTerms, ['business', 'economy', 'finance', 'market'])) {
                return 'professional business meeting office';
            } elseif (containsKeywords($searchTerms, ['technology', 'tech', 'software', 'digital'])) {
                return 'modern technology workspace computer';
            } elseif (containsKeywords($searchTerms, ['sports', 'game', 'team', 'player'])) {
                return 'sports stadium athletic competition';
            } elseif (containsKeywords($searchTerms, ['politics', 'government', 'election', 'policy'])) {
                return 'government building political meeting';
            } elseif (containsKeywords($searchTerms, ['health', 'medical', 'doctor', 'hospital'])) {
                return 'medical professional healthcare';
            } elseif (containsKeywords($searchTerms, ['education', 'school', 'university', 'student'])) {
                return 'education classroom students learning';
            } elseif (containsKeywords($searchTerms, ['entertainment', 'movie', 'music', 'celebrity'])) {
                return 'entertainment event red carpet';
            } else {
                return 'professional news photo ' . $searchTerms;
            }
            break;
            
        case 'ad':
            return 'professional advertisement banner marketing';
            
        default:
            return 'professional photo ' . $searchTerms;
    }
}

/**
 * Get stock images using the stock image tool (mock implementation)
 * This would use the actual stock_image_tool in the real implementation
 * @param string $description
 * @return array
 */
function getStockImages($description) {
    // For now, return existing stock images based on description
    // In a real implementation, this would call the stock_image_tool
    
    $stockImagesDir = '../attached_assets/stock_images/';
    $existingImages = [];
    
    if (is_dir($stockImagesDir)) {
        $files = scandir($stockImagesDir);
        foreach ($files as $file) {
            if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $existingImages[] = $stockImagesDir . $file;
            }
        }
    }
    
    // Return a random existing stock image for now
    if (!empty($existingImages)) {
        shuffle($existingImages);
        return [$existingImages[0]];
    }
    
    return [];
}

/**
 * Download and store image in the uploads directory
 * @param string $imagePath
 * @param string $type
 * @return string|false
 */
function downloadAndStoreImage($imagePath, $type) {
    $uploadsDir = '../uploads/';
    
    // Ensure uploads directory exists
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate unique filename
    $timestamp = time();
    $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
    $filename = $timestamp . '_auto_' . $type . '_' . uniqid() . '.' . $extension;
    $destinationPath = $uploadsDir . $filename;
    
    try {
        // If it's a local file, copy it
        if (file_exists($imagePath)) {
            if (copy($imagePath, $destinationPath)) {
                // Return the web-accessible path
                return '../uploads/' . $filename;
            }
        }
        // If it's a URL, download it
        elseif (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $imageData = file_get_contents($imagePath);
            if ($imageData !== false) {
                if (file_put_contents($destinationPath, $imageData) !== false) {
                    return '../uploads/' . $filename;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Failed to download/store image: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Get default fallback image based on type
 * @param string $type
 * @return string
 */
function getDefaultImage($type) {
    $defaultImages = [
        'article' => '../public/images/default-article.jpg',
        'ad' => '../public/images/default-ad.jpg',
        'general' => '../public/images/default.jpg'
    ];
    
    $defaultPath = isset($defaultImages[$type]) ? $defaultImages[$type] : $defaultImages['general'];
    
    // Check if default image exists, if not return a simple placeholder
    if (file_exists($defaultPath)) {
        return $defaultPath;
    }
    
    // Return a data URL placeholder if no default image exists
    return 'data:image/svg+xml;base64,' . base64_encode('
        <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
            <rect width="300" height="200" fill="#f8f9fa"/>
            <text x="150" y="100" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="14">
                Default Image
            </text>
        </svg>
    ');
}

/**
 * Get an appropriate stock image based on content/category
 * @param string $context The context (article title, category name, etc.)
 * @param string $type The type of content (article, ad, etc.)
 * @return string|false The path to the assigned image or false on failure
 */
function getAutoAssignedImage($context, $type = 'article') {
    // Sanitize context for use in image search
    $searchTerms = sanitizeForImageSearch($context);
    
    // Define search terms based on type and context
    $imageDescription = generateImageDescription($searchTerms, $type);
    
    try {
        // Use stock image tool to get appropriate image
        $stockImages = getStockImages($imageDescription);
        
        if (!empty($stockImages)) {
            // Download and store the first suitable image
            $imageUrl = downloadAndStoreImage($stockImages[0], $type);
            return $imageUrl;
        }
    } catch (Exception $e) {
        error_log("Auto image assignment failed: " . $e->getMessage());
    }
    
    // Fallback to default images if stock image fails
    return getDefaultImage($type);
}

/**
 * Auto-assign image for article if none provided
 * @param array $articleData
 * @return string|null
 */
function autoAssignArticleImage($articleData) {
    // Check if featured_image is already provided
    if (!empty($articleData['featured_image'])) {
        return $articleData['featured_image'];
    }
    
    // Create context from title and category
    $context = $articleData['title'] ?? '';
    if (!empty($articleData['category_name'])) {
        $context .= ' ' . $articleData['category_name'];
    }
    
    return getAutoAssignedImage($context, 'article');
}

/**
 * Auto-assign image for advertisement if none provided
 * @param array $adData
 * @return string|null
 */
function autoAssignAdImage($adData) {
    // Check if image is already provided
    if (!empty($adData['image_url'])) {
        return $adData['image_url'];
    }
    
    // Create context from title and description
    $context = $adData['title'] ?? '';
    if (!empty($adData['description'])) {
        $context .= ' ' . strip_tags($adData['description']);
    }
    
    return getAutoAssignedImage($context, 'ad');
}
?>