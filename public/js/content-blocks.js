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
                <span class="block-type">Text Block</span>
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
                <textarea class="form-control" rows="8" placeholder="Enter your content here..." 
                          name="blocks[${blockId}][content]">${content}</textarea>
                <input type="hidden" name="blocks[${blockId}][type]" value="text">
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
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
                <span class="block-type">Image Block</span>
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
                <input type="hidden" name="blocks[${blockId}][type]" value="image">
                <input type="hidden" name="blocks[${blockId}][url]" value="${imageUrl}" id="url_${blockId}">
                <div class="mt-2">
                    <input type="text" class="form-control" placeholder="Image caption (optional)" 
                           name="blocks[${blockId}][caption]" value="${caption}">
                </div>
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
        
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
                <span class="block-type">Video Block</span>
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
                <div class="mb-2">
                    <input type="text" class="form-control" placeholder="Video URL (YouTube, Vimeo, etc.)" 
                           name="blocks[${blockId}][url]" value="${videoUrl}" 
                           onchange="contentEditor.updateVideoPreview('${blockId}', this.value)">
                </div>
                <div class="video-preview" id="preview_${blockId}">
                    ${videoUrl ? this.generateVideoEmbed(videoUrl) : '<p class="text-muted">Enter video URL to see preview</p>'}
                </div>
                <input type="hidden" name="blocks[${blockId}][type]" value="video">
                <div class="mt-2">
                    <input type="text" class="form-control" placeholder="Video caption (optional)" 
                           name="blocks[${blockId}][caption]" value="${caption}">
                </div>
            </div>
        `;
        
        this.blocksContainer.appendChild(block);
        this.blocks.push(blockId);
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
    
    updateVideoPreview(blockId, url) {
        const preview = document.getElementById(`preview_${blockId}`);
        if (url) {
            preview.innerHTML = this.generateVideoEmbed(url);
        } else {
            preview.innerHTML = '<p class="text-muted">Enter video URL to see preview</p>';
        }
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
                    blockData.content = block.querySelector('textarea').value;
                    break;
                case 'image':
                    blockData.url = block.querySelector('input[name*="[url]"]').value;
                    blockData.caption = block.querySelector('input[name*="[caption]"]').value;
                    break;
                case 'video':
                    blockData.url = block.querySelector('input[name*="[url]"]').value;
                    blockData.caption = block.querySelector('input[name*="[caption]"]').value;
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
}

// Initialize when DOM is ready
let contentEditor;
document.addEventListener('DOMContentLoaded', function() {
    const editorContainer = document.getElementById('content-blocks-editor');
    if (editorContainer) {
        contentEditor = new ContentBlocksEditor(editorContainer);
    }
});