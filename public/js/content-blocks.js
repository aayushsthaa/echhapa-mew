// Content Blocks Editor System
class ContentBlocksEditor {
    constructor(container) {
        this.container = container;
        this.blocks = [];
        this.blockCounter = 0;
        this.init();
    }
    
    init() {
        this.container.innerHTML = '';
        this.createToolbar();
        this.createBlocksContainer();
        this.addTextBlock(); // Start with one text block
    }
    
    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'content-blocks-toolbar';
        toolbar.innerHTML = `
            <div class="toolbar-buttons">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="contentEditor.addTextBlock()">
                    <i class="fas fa-paragraph"></i> Add Text
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="contentEditor.addImageBlock()">
                    <i class="fas fa-image"></i> Add Image
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="contentEditor.addVideoBlock()">
                    <i class="fas fa-video"></i> Add Video
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="contentEditor.addQuoteBlock()">
                    <i class="fas fa-quote-left"></i> Add Quote
                </button>
            </div>
        `;
        this.container.appendChild(toolbar);
    }
    
    createBlocksContainer() {
        this.blocksContainer = document.createElement('div');
        this.blocksContainer.className = 'content-blocks-container';
        this.container.appendChild(this.blocksContainer);
    }
    
    addTextBlock(content = '') {
        const blockId = 'block_' + (++this.blockCounter);
        const block = document.createElement('div');
        block.className = 'content-block text-block';
        block.dataset.blockId = blockId;
        block.dataset.blockType = 'text';
        
        block.innerHTML = `
            <div class="block-header">
                <span class="block-type"><i class="fas fa-font text-primary"></i> Content Block</span>
                <div class="block-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockUp('${blockId}')" title="Move Up">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockDown('${blockId}')" title="Move Down">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="contentEditor.removeBlock('${blockId}')" title="Remove Block">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div id="editor_${blockId}" class="rich-text-editor" data-editor data-height="300px"></div>
                <textarea name="blocks[${blockId}][content]" style="display:none;" id="content_${blockId}">${content}</textarea>
                <input type="hidden" name="blocks[${blockId}][type]" value="text">
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
        
        // Initialize the professional editor
        setTimeout(() => {
            try {
                const editor = new ProfessionalEditor(`editor_${blockId}`, {
                    height: '300px',
                    placeholder: 'Start writing your content...'
                });
                
                // Store editor reference
                block.editorInstance = editor;
                
                // Set initial content if provided
                if (content) {
                    setTimeout(() => {
                        editor.setContent(content);
                    }, 200);
                }
                
                // Sync content with hidden textarea on change
                if (editor.editorArea) {
                    editor.editorArea.addEventListener('input', () => {
                        document.getElementById(`content_${blockId}`).value = editor.getContent();
                    });
                    
                    editor.editorArea.addEventListener('blur', () => {
                        document.getElementById(`content_${blockId}`).value = editor.getContent();
                    });
                }
            } catch (error) {
                console.error('Error initializing rich text editor:', error);
                // Fallback to textarea
                const editorDiv = document.getElementById(`editor_${blockId}`);
                editorDiv.innerHTML = `
                    <textarea class="form-control" rows="8" placeholder="Start writing your content..." 
                              onchange="document.getElementById('content_${blockId}').value = this.value"
                              oninput="document.getElementById('content_${blockId}').value = this.value">${content}</textarea>
                `;
            }
        }, 100);
        
        return blockId;
    }
    
    addImageBlock(imageUrl = '', caption = '') {
        const blockId = 'block_' + (++this.blockCounter);
        const block = document.createElement('div');
        block.className = 'content-block image-block';
        block.dataset.blockId = blockId;
        block.dataset.blockType = 'image';
        
        block.innerHTML = `
            <div class="block-header">
                <span class="block-type"><i class="fas fa-image text-success"></i> Image Block</span>
                <div class="block-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockUp('${blockId}')">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockDown('${blockId}')">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="contentEditor.removeBlock('${blockId}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <!-- Image Input Options -->
                <div class="image-input-options mb-3">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="imageSource_${blockId}" id="upload_${blockId}_radio" value="upload" checked>
                        <label class="btn btn-outline-primary" for="upload_${blockId}_radio">
                            <i class="fas fa-upload"></i> Upload Image
                        </label>
                        
                        <input type="radio" class="btn-check" name="imageSource_${blockId}" id="url_${blockId}_radio" value="url">
                        <label class="btn btn-outline-primary" for="url_${blockId}_radio">
                            <i class="fas fa-link"></i> Image URL
                        </label>
                    </div>
                </div>
                
                <!-- Upload Option -->
                <div class="upload-option" id="uploadOption_${blockId}">
                    <div class="image-upload-area ${imageUrl ? 'has-image' : ''}" id="upload_${blockId}">
                        ${imageUrl ? `<img src="${imageUrl}" alt="Uploaded image">` : ''}
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload image or drag and drop</p>
                            <p class="text-muted">Max size: 5MB (JPG, PNG, GIF, WebP)</p>
                        </div>
                    </div>
                    <input type="file" id="file_${blockId}" accept="image/*" style="display: none;" 
                           onchange="contentEditor.handleImageUpload('${blockId}', this)">
                </div>
                
                <!-- URL Option -->
                <div class="url-option" id="urlOption_${blockId}" style="display: none;">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="imageUrl_${blockId}" 
                               placeholder="Enter image URL (e.g., https://example.com/image.jpg)" 
                               value="${imageUrl}">
                        <button type="button" class="btn btn-outline-secondary" 
                                onclick="contentEditor.loadImageFromUrl('${blockId}')">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                    <div class="url-preview" id="urlPreview_${blockId}">
                        ${imageUrl ? `<img src="${imageUrl}" alt="URL image preview" style="max-width: 100%; height: auto;">` : '<p class="text-muted">Enter URL and click Preview to see image</p>'}
                    </div>
                </div>
                
                <input type="hidden" name="blocks[${blockId}][type]" value="image">
                <input type="hidden" name="blocks[${blockId}][url]" value="${imageUrl}" id="url_${blockId}">
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Image caption (optional)" 
                                   name="blocks[${blockId}][caption]" value="${caption}">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Alt text for accessibility" 
                                   name="blocks[${blockId}][alt]" value="">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <input type="url" class="form-control" placeholder="Link URL (optional)" 
                                   name="blocks[${blockId}][link]" value="">
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="blocks[${blockId}][alignment]">
                                <option value="center">Center Aligned</option>
                                <option value="left">Left Aligned</option>
                                <option value="right">Right Aligned</option>
                                <option value="full">Full Width</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
        
        // Set up image source toggle
        const uploadRadio = block.querySelector(`#upload_${blockId}_radio`);
        const urlRadio = block.querySelector(`#url_${blockId}_radio`);
        const uploadOption = block.querySelector(`#uploadOption_${blockId}`);
        const urlOption = block.querySelector(`#urlOption_${blockId}`);
        
        uploadRadio.onchange = () => {
            if (uploadRadio.checked) {
                uploadOption.style.display = 'block';
                urlOption.style.display = 'none';
            }
        };
        
        urlRadio.onchange = () => {
            if (urlRadio.checked) {
                uploadOption.style.display = 'none';
                urlOption.style.display = 'block';
            }
        };
        
        // Set up click handler for upload area
        const uploadArea = block.querySelector('.image-upload-area');
        const fileInput = block.querySelector(`#file_${blockId}`);
        
        uploadArea.onclick = () => fileInput.click();
        
        // Set up drag and drop
        uploadArea.ondragover = (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        };
        
        uploadArea.ondragleave = () => {
            uploadArea.classList.remove('drag-over');
        };
        
        uploadArea.ondrop = (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                this.handleImageUpload(blockId, fileInput);
            }
        };
        
        return blockId;
    }
    
    addVideoBlock(videoUrl = '', caption = '') {
        const blockId = 'block_' + (++this.blockCounter);
        const block = document.createElement('div');
        block.className = 'content-block video-block';
        block.dataset.blockId = blockId;
        block.dataset.blockType = 'video';
        
        block.innerHTML = `
            <div class="block-header">
                <span class="block-type"><i class="fas fa-video text-info"></i> Video Block</span>
                <div class="block-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockUp('${blockId}')">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockDown('${blockId}')">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="contentEditor.removeBlock('${blockId}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="video-input-options mb-3">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="videoSource_${blockId}" id="youtube_${blockId}" value="youtube" checked>
                        <label class="btn btn-outline-danger" for="youtube_${blockId}">
                            <i class="fab fa-youtube"></i> YouTube
                        </label>
                        
                        <input type="radio" class="btn-check" name="videoSource_${blockId}" id="vimeo_${blockId}" value="vimeo">
                        <label class="btn btn-outline-info" for="vimeo_${blockId}">
                            <i class="fab fa-vimeo"></i> Vimeo
                        </label>
                        
                        <input type="radio" class="btn-check" name="videoSource_${blockId}" id="embed_${blockId}" value="embed">
                        <label class="btn btn-outline-secondary" for="embed_${blockId}">
                            <i class="fas fa-code"></i> Embed Code
                        </label>
                    </div>
                </div>
                
                <div class="url-input-section" id="urlSection_${blockId}">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="videoUrlInput_${blockId}" 
                               placeholder="Paste YouTube or Vimeo URL here..." 
                               value="${videoUrl}" 
                               onchange="contentEditor.updateVideoPreview('${blockId}', this.value)">
                        <button type="button" class="btn btn-primary" onclick="contentEditor.loadVideoFromUrl('${blockId}')">
                            <i class="fas fa-play"></i> Load
                        </button>
                    </div>
                    <small class="text-muted">Examples: https://www.youtube.com/watch?v=... or https://vimeo.com/...</small>
                </div>
                
                <div class="embed-input-section" id="embedSection_${blockId}" style="display: none;">
                    <div class="mb-2">
                        <label class="form-label">Custom Embed Code</label>
                        <textarea class="form-control" id="embedCodeInput_${blockId}" rows="4" 
                                  placeholder="Paste iframe or embed code here..."></textarea>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="contentEditor.loadVideoFromEmbed('${blockId}')">
                        <i class="fas fa-code"></i> Load Embed
                    </button>
                </div>
                
                <div class="video-preview mt-3" id="preview_${blockId}">
                    ${videoUrl ? this.generateVideoEmbed(videoUrl) : '<p class="text-muted">Enter video URL to see preview</p>'}
                </div>
                
                <input type="hidden" name="blocks[${blockId}][type]" value="video">
                <input type="hidden" name="blocks[${blockId}][url]" value="${videoUrl}" id="videoUrl_${blockId}">
                <input type="hidden" name="blocks[${blockId}][embed_code]" value="" id="embedCode_${blockId}">
                
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Video caption (optional)" 
                                   name="blocks[${blockId}][caption]" value="${caption}">
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" name="blocks[${blockId}][alignment]">
                                <option value="center">Center Aligned</option>
                                <option value="left">Left Aligned</option>
                                <option value="right">Right Aligned</option>
                                <option value="full">Full Width</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
        
        // Setup video source switching
        const videoSourceRadios = block.querySelectorAll(`input[name="videoSource_${blockId}"]`);
        const urlSection = block.querySelector(`#urlSection_${blockId}`);
        const embedSection = block.querySelector(`#embedSection_${blockId}`);
        const urlInput = block.querySelector(`#videoUrlInput_${blockId}`);
        
        videoSourceRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'embed') {
                    urlSection.style.display = 'none';
                    embedSection.style.display = 'block';
                } else {
                    urlSection.style.display = 'block';
                    embedSection.style.display = 'none';
                    
                    // Update placeholder based on selection
                    if (radio.value === 'youtube') {
                        urlInput.placeholder = 'Paste YouTube URL here...';
                    } else if (radio.value === 'vimeo') {
                        urlInput.placeholder = 'Paste Vimeo URL here...';
                    }
                }
            });
        });
        
        return blockId;
    }
    
    addQuoteBlock(quote = '', author = '') {
        const blockId = 'block_' + (++this.blockCounter);
        const block = document.createElement('div');
        block.className = 'content-block quote-block';
        block.dataset.blockId = blockId;
        block.dataset.blockType = 'quote';
        
        block.innerHTML = `
            <div class="block-header">
                <span class="block-type">Quote Block</span>
                <div class="block-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockUp('${blockId}')">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="contentEditor.moveBlockDown('${blockId}')">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="contentEditor.removeBlock('${blockId}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <textarea class="form-control mb-2" rows="4" placeholder="Enter quote text..." 
                          name="blocks[${blockId}][content]">${quote}</textarea>
                <input type="text" class="form-control" placeholder="Quote author (optional)" 
                       name="blocks[${blockId}][author]" value="${author}">
                <input type="hidden" name="blocks[${blockId}][type]" value="quote">
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
        return blockId;
    }
    
    async handleImageUpload(blockId, fileInput) {
        const file = fileInput.files[0];
        if (!file) return;
        
        const uploadArea = document.getElementById(`upload_${blockId}`);
        const urlInput = document.getElementById(`url_${blockId}`);
        
        uploadArea.innerHTML = '<div class="upload-progress"><i class="fas fa-spinner fa-spin"></i> Uploading...</div>';
        
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            const response = await fetch('upload-image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                uploadArea.innerHTML = `<img src="${result.url}" alt="Uploaded image">`;
                uploadArea.classList.add('has-image');
                urlInput.value = result.url;
            } else {
                uploadArea.innerHTML = `
                    <div class="upload-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Upload failed: ${result.message}</p>
                    </div>
                `;
                setTimeout(() => {
                    uploadArea.innerHTML = `
                        <div class="upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload image or drag and drop</p>
                            <p class="text-muted">Max size: 5MB (JPG, PNG, GIF, WebP)</p>
                        </div>
                    `;
                    uploadArea.classList.remove('has-image');
                }, 3000);
            }
        } catch (error) {
            uploadArea.innerHTML = `
                <div class="upload-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Upload failed: Network error</p>
                </div>
            `;
        }
    }
    
    loadImageFromUrl(blockId) {
        const imageUrlInput = document.getElementById(`imageUrl_${blockId}`);
        const urlPreview = document.getElementById(`urlPreview_${blockId}`);
        const hiddenUrlInput = document.getElementById(`url_${blockId}`);
        
        const imageUrl = imageUrlInput.value.trim();
        
        if (!imageUrl) {
            urlPreview.innerHTML = '<p class="text-muted">Please enter an image URL</p>';
            return;
        }
        
        // Basic URL validation
        try {
            new URL(imageUrl);
        } catch {
            urlPreview.innerHTML = '<p class="text-danger">Please enter a valid URL</p>';
            return;
        }
        
        // Check if it's likely an image URL
        const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp'];
        const hasImageExtension = imageExtensions.some(ext => 
            imageUrl.toLowerCase().includes(ext)
        );
        
        if (!hasImageExtension && !imageUrl.includes('unsplash.com') && !imageUrl.includes('imgur.com') && !imageUrl.includes('pixabay.com')) {
            const proceed = confirm('This URL might not be an image. Do you want to try loading it anyway?');
            if (!proceed) return;
        }
        
        urlPreview.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading image...</div>';
        
        // Create image element to test if URL works
        const img = new Image();
        
        img.onload = function() {
            urlPreview.innerHTML = `<img src="${imageUrl}" alt="URL image preview" style="max-width: 100%; height: auto; border: 1px solid #dee2e6; border-radius: 4px;">`;
            hiddenUrlInput.value = imageUrl;
        };
        
        img.onerror = function() {
            urlPreview.innerHTML = `
                <div class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load image from this URL</p>
                    <p class="small">Please check the URL or try a different image</p>
                </div>
            `;
        };
        
        img.src = imageUrl;
    }
    
    updateVideoPreview(blockId, url) {
        const preview = document.getElementById(`preview_${blockId}`);
        const hiddenUrlInput = document.getElementById(`videoUrl_${blockId}`);
        
        if (url) {
            preview.innerHTML = this.generateVideoEmbed(url);
            hiddenUrlInput.value = url;
        } else {
            preview.innerHTML = '<p class="text-muted">Enter video URL to see preview</p>';
            hiddenUrlInput.value = '';
        }
    }
    
    loadVideoFromUrl(blockId) {
        const urlInput = document.getElementById(`videoUrlInput_${blockId}`);
        const url = urlInput.value.trim();
        
        if (!url) {
            alert('Please enter a video URL.');
            return;
        }
        
        this.updateVideoPreview(blockId, url);
    }
    
    loadVideoFromEmbed(blockId) {
        const embedInput = document.getElementById(`embedCodeInput_${blockId}`);
        const embedCode = embedInput.value.trim();
        
        if (!embedCode) {
            alert('Please enter embed code.');
            return;
        }
        
        const preview = document.getElementById(`preview_${blockId}`);
        const hiddenEmbedInput = document.getElementById(`embedCode_${blockId}`);
        
        preview.innerHTML = embedCode;
        hiddenEmbedInput.value = embedCode;
    }
    
    generateVideoEmbed(url) {
        // Handle YouTube URLs
        const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
        if (youtubeMatch) {
            return `<iframe width="100%" height="315" src="https://www.youtube.com/embed/${youtubeMatch[1]}" 
                    frameborder="0" allowfullscreen></iframe>`;
        }
        
        // Handle Vimeo URLs
        const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) {
            return `<iframe width="100%" height="315" src="https://player.vimeo.com/video/${vimeoMatch[1]}" 
                    frameborder="0" allowfullscreen></iframe>`;
        }
        
        // Fallback for other video URLs
        return `<video width="100%" controls>
                    <source src="${url}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>`;
    }
    
    moveBlockUp(blockId) {
        const block = document.querySelector(`[data-block-id="${blockId}"]`);
        const prevBlock = block.previousElementSibling;
        if (prevBlock) {
            block.parentNode.insertBefore(block, prevBlock);
        }
    }
    
    moveBlockDown(blockId) {
        const block = document.querySelector(`[data-block-id="${blockId}"]`);
        const nextBlock = block.nextElementSibling;
        if (nextBlock) {
            block.parentNode.insertBefore(nextBlock, block);
        }
    }
    
    removeBlock(blockId) {
        if (this.blocks.length <= 1) {
            alert('You must have at least one content block.');
            return;
        }
        
        if (confirm('Are you sure you want to remove this block?')) {
            const block = document.querySelector(`[data-block-id="${blockId}"]`);
            block.remove();
            this.blocks = this.blocks.filter(id => id !== blockId);
        }
    }
    
    getContent() {
        const blocks = [];
        const blockElements = this.blocksContainer.querySelectorAll('.content-block');
        
        blockElements.forEach(block => {
            const blockId = block.dataset.blockId;
            const blockType = block.dataset.blockType;
            const blockData = { type: blockType, id: blockId };
            
            switch (blockType) {
                case 'text':
                    // Get content from rich text editor or fallback textarea
                    if (block.editorInstance && block.editorInstance.getContent) {
                        blockData.content = block.editorInstance.getContent();
                    } else {
                        // Try hidden textarea
                        const hiddenTextarea = block.querySelector(`#content_${blockId}`);
                        if (hiddenTextarea) {
                            blockData.content = hiddenTextarea.value;
                        } else {
                            // Fallback to visible textarea
                            const textarea = block.querySelector('textarea');
                            blockData.content = textarea ? textarea.value : '';
                        }
                    }
                    break;
                case 'image':
                    blockData.url = block.querySelector('input[name*="[url]"]').value;
                    blockData.caption = block.querySelector('input[name*="[caption]"]').value;
                    blockData.alt = block.querySelector('input[name*="[alt]"]')?.value || '';
                    blockData.link = block.querySelector('input[name*="[link]"]')?.value || '';
                    blockData.alignment = block.querySelector('select[name*="[alignment]"]')?.value || 'center';
                    break;
                case 'video':
                    blockData.url = block.querySelector('input[name*="[url]"]').value;
                    blockData.embed_code = block.querySelector('input[name*="[embed_code]"]')?.value || '';
                    blockData.caption = block.querySelector('input[name*="[caption]"]').value;
                    blockData.alignment = block.querySelector('select[name*="[alignment]"]')?.value || 'center';
                    break;
                case 'quote':
                    blockData.content = block.querySelector('textarea').value;
                    blockData.author = block.querySelector('input[name*="[author]"]').value;
                    break;
            }
            
            blocks.push(blockData);
        });
        
        return blocks;
    }
    
    // Method to get final content for form submission
    getFinalContent() {
        // Update all rich text editor hidden textareas before getting content
        this.blocksContainer.querySelectorAll('.content-block.text-block').forEach(block => {
            const blockId = block.dataset.blockId;
            if (block.editorInstance && block.editorInstance.getContent) {
                const hiddenTextarea = document.getElementById(`content_${blockId}`);
                if (hiddenTextarea) {
                    hiddenTextarea.value = block.editorInstance.getContent();
                }
            }
        });
        
        return JSON.stringify(this.getContent());
    }
}

// Initialize when DOM is ready
let contentEditor;
document.addEventListener('DOMContentLoaded', function() {
    const editorContainer = document.getElementById('content-blocks-editor');
    if (editorContainer) {
        contentEditor = new ContentBlocksEditor(editorContainer);
    }
});