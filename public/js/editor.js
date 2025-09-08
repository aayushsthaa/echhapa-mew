/**
 * Professional Rich Text Editor
 * Custom implementation inspired by modern news platforms
 */
class ProfessionalEditor {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            height: '400px',
            placeholder: 'Start writing your article...',
            ...options
        };
        
        this.init();
        this.bindEvents();
    }

    init() {
        // Create editor structure
        this.container.innerHTML = `
            <div class="editor-container">
                <div class="editor-toolbar">
                    <select class="editor-select" data-command="formatBlock">
                        <option value="p">Paragraph</option>
                        <option value="h1">Heading 1</option>
                        <option value="h2">Heading 2</option>
                        <option value="h3">Heading 3</option>
                        <option value="h4">Heading 4</option>
                        <option value="h5">Heading 5</option>
                        <option value="h6">Heading 6</option>
                    </select>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="bold" title="Bold">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="italic" title="Italic">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="underline" title="Underline">
                        <i class="fas fa-underline"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="strikeThrough" title="Strikethrough">
                        <i class="fas fa-strikethrough"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="justifyLeft" title="Align Left">
                        <i class="fas fa-align-left"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="justifyCenter" title="Align Center">
                        <i class="fas fa-align-center"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="justifyRight" title="Align Right">
                        <i class="fas fa-align-right"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="justifyFull" title="Justify">
                        <i class="fas fa-align-justify"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="insertUnorderedList" title="Bullet List">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertOrderedList" title="Numbered List">
                        <i class="fas fa-list-ol"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="outdent" title="Decrease Indent">
                        <i class="fas fa-outdent"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="indent" title="Increase Indent">
                        <i class="fas fa-indent"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="createLink" title="Insert Link">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="unlink" title="Remove Link">
                        <i class="fas fa-unlink"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertImage" title="Insert Image">
                        <i class="fas fa-image"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertVideo" title="Insert Video">
                        <i class="fas fa-video"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertTable" title="Insert Table">
                        <i class="fas fa-table"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="formatBlock" data-value="blockquote" title="Quote">
                        <i class="fas fa-quote-left"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="insertHorizontalRule" title="Insert Horizontal Line">
                        <i class="fas fa-minus"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="undo" title="Undo">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="editor-btn" data-command="redo" title="Redo">
                        <i class="fas fa-redo"></i>
                    </button>
                    
                    <button type="button" class="editor-btn" data-command="removeFormat" title="Clear Formatting">
                        <i class="fas fa-eraser"></i>
                    </button>
                    
                    <div class="editor-separator"></div>
                    
                    <button type="button" class="editor-btn" data-command="viewSource" title="View Source">
                        <i class="fas fa-code"></i>
                    </button>
                </div>
                
                <div class="editor-content" contenteditable="true" style="min-height: ${this.options.height}"></div>
            </div>
        `;

        this.toolbar = this.container.querySelector('.editor-toolbar');
        this.content = this.container.querySelector('.editor-content');
        this.sourceMode = false;
    }

    bindEvents() {
        // Toolbar button events
        this.toolbar.addEventListener('click', (e) => {
            if (e.target.classList.contains('editor-btn') || e.target.closest('.editor-btn')) {
                e.preventDefault();
                const btn = e.target.classList.contains('editor-btn') ? e.target : e.target.closest('.editor-btn');
                const command = btn.getAttribute('data-command');
                const value = btn.getAttribute('data-value');
                
                this.executeCommand(command, value);
            }
        });

        // Format block select
        this.toolbar.addEventListener('change', (e) => {
            if (e.target.classList.contains('editor-select')) {
                const command = e.target.getAttribute('data-command');
                const value = e.target.value;
                this.executeCommand(command, value);
            }
        });

        // Update toolbar state on selection change
        this.content.addEventListener('keyup', () => this.updateToolbarState());
        this.content.addEventListener('mouseup', () => this.updateToolbarState());

        // Handle paste events
        this.content.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });

        // Auto-save functionality
        this.content.addEventListener('input', () => {
            if (this.options.autoSave) {
                clearTimeout(this.autoSaveTimer);
                this.autoSaveTimer = setTimeout(() => {
                    this.save();
                }, 2000);
            }
        });
    }

    executeCommand(command, value = null) {
        this.content.focus();

        switch (command) {
            case 'createLink':
                this.insertLink();
                break;
            case 'insertImage':
                this.insertImage();
                break;
            case 'insertVideo':
                this.insertVideo();
                break;
            case 'insertTable':
                this.insertTable();
                break;
            case 'viewSource':
                this.toggleSourceMode();
                break;
            default:
                document.execCommand(command, false, value);
                break;
        }

        this.updateToolbarState();
    }

    insertLink() {
        const selection = window.getSelection();
        const selectedText = selection.toString();
        const url = prompt('Enter the URL:', 'https://');
        
        if (url) {
            if (selectedText) {
                document.execCommand('createLink', false, url);
            } else {
                const linkText = prompt('Enter link text:', 'Link');
                if (linkText) {
                    const link = `<a href="${url}" target="_blank">${linkText}</a>`;
                    document.execCommand('insertHTML', false, link);
                }
            }
        }
    }

    insertImage() {
        // Create a modal for image insertion options
        const modal = document.createElement('div');
        modal.className = 'image-insert-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
                <h5 style="margin-bottom: 1rem;">Insert Image</h5>
                <div class="tab-buttons" style="margin-bottom: 1rem; border-bottom: 1px solid #dee2e6;">
                    <button type="button" class="tab-btn active" data-tab="upload" style="padding: 0.5rem 1rem; border: none; background: none; border-bottom: 2px solid #007bff; cursor: pointer;">Upload File</button>
                    <button type="button" class="tab-btn" data-tab="url" style="padding: 0.5rem 1rem; border: none; background: none; border-bottom: 2px solid transparent; cursor: pointer; margin-left: 1rem;">From URL</button>
                </div>
                
                <div class="tab-content">
                    <div class="tab-panel" id="upload-panel">
                        <div class="upload-area" style="border: 2px dashed #dee2e6; padding: 2rem; text-align: center; border-radius: 4px; cursor: pointer; margin-bottom: 1rem;">
                            <i class="fas fa-upload" style="font-size: 2rem; color: #6c757d; margin-bottom: 1rem;"></i>
                            <p style="margin: 0; color: #6c757d;">Click to select image or drag and drop</p>
                            <small style="color: #6c757d;">JPG, PNG, GIF, WebP (Max: 5MB)</small>
                        </div>
                        <input type="file" accept="image/*" style="display: none;">
                    </div>
                    
                    <div class="tab-panel" id="url-panel" style="display: none;">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem;">Image URL:</label>
                            <input type="url" class="form-control" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem;">Alt Text (optional):</label>
                            <input type="text" class="form-control" placeholder="Describe the image" style="width: 100%; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px;">
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn-cancel" style="padding: 0.5rem 1rem; margin-right: 0.5rem; border: 1px solid #dee2e6; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="button" class="btn-insert" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Insert Image</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Tab switching
        const tabButtons = modal.querySelectorAll('.tab-btn');
        const tabPanels = modal.querySelectorAll('.tab-panel');
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => {
                    b.classList.remove('active');
                    b.style.borderBottomColor = 'transparent';
                });
                btn.classList.add('active');
                btn.style.borderBottomColor = '#007bff';
                
                tabPanels.forEach(panel => panel.style.display = 'none');
                modal.querySelector(`#${btn.dataset.tab}-panel`).style.display = 'block';
            });
        });
        
        // File upload handling
        const uploadArea = modal.querySelector('.upload-area');
        const fileInput = modal.querySelector('input[type="file"]');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            uploadArea.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i><p style="margin-top: 1rem; color: #007bff;">Uploading...</p>';
            
            const formData = new FormData();
            formData.append('image', file);
            
            try {
                const response = await fetch('../admin/upload-image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const img = `<img src="${result.url}" alt="${file.name}" style="max-width: 100%; height: auto; margin: 10px 0;">`;
                    document.execCommand('insertHTML', false, img);
                    document.body.removeChild(modal);
                } else {
                    uploadArea.innerHTML = `<i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i><p style="margin-top: 1rem; color: #dc3545;">Upload failed: ${result.message}</p>`;
                }
            } catch (error) {
                uploadArea.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i><p style="margin-top: 1rem; color: #dc3545;">Upload failed</p>';
            }
        });
        
        // URL insertion handling
        const urlInput = modal.querySelector('#url-panel input[type="url"]');
        const altInput = modal.querySelector('#url-panel input[type="text"]');
        const insertBtn = modal.querySelector('.btn-insert');
        const cancelBtn = modal.querySelector('.btn-cancel');
        
        insertBtn.addEventListener('click', () => {
            const activePanel = modal.querySelector('.tab-panel:not([style*="display: none"])');
            
            if (activePanel.id === 'url-panel') {
                const url = urlInput.value.trim();
                const alt = altInput.value.trim();
                
                if (url) {
                    const img = `<img src="${url}" alt="${alt}" style="max-width: 100%; height: auto; margin: 10px 0;">`;
                    document.execCommand('insertHTML', false, img);
                    document.body.removeChild(modal);
                } else {
                    alert('Please enter an image URL');
                }
            }
        });
        
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    insertVideo() {
        // Create a modal for video insertion options
        const modal = document.createElement('div');
        modal.className = 'video-insert-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
                <h5 style="margin-bottom: 1rem;">Insert Video</h5>
                <div class="tab-buttons" style="margin-bottom: 1rem; border-bottom: 1px solid #dee2e6;">
                    <button type="button" class="tab-btn active" data-tab="embed" style="padding: 0.5rem 1rem; border: none; background: none; border-bottom: 2px solid #007bff; cursor: pointer;">YouTube/Vimeo</button>
                    <button type="button" class="tab-btn" data-tab="upload" style="padding: 0.5rem 1rem; border: none; background: none; border-bottom: 2px solid transparent; cursor: pointer; margin-left: 1rem;">Upload File</button>
                </div>
                
                <div class="tab-content">
                    <div class="tab-panel" id="embed-panel">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem;">Video URL:</label>
                            <input type="url" class="video-url" placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..." style="width: 100%; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px; margin-bottom: 0.5rem;">
                            <small style="color: #6c757d;">Supports YouTube, Vimeo, and direct video URLs</small>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem;">Width (optional):</label>
                            <input type="number" class="video-width" placeholder="560" style="width: 100px; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px;">
                            <span style="margin: 0 0.5rem;">×</span>
                            <input type="number" class="video-height" placeholder="315" style="width: 100px; padding: 0.5rem; border: 1px solid #dee2e6; border-radius: 4px;">
                        </div>
                    </div>
                    
                    <div class="tab-panel" id="upload-panel" style="display: none;">
                        <div class="upload-area" style="border: 2px dashed #dee2e6; padding: 2rem; text-align: center; border-radius: 4px; cursor: pointer; margin-bottom: 1rem;">
                            <i class="fas fa-video" style="font-size: 2rem; color: #6c757d; margin-bottom: 1rem;"></i>
                            <p style="margin: 0; color: #6c757d;">Click to select video file</p>
                            <small style="color: #6c757d;">MP4, WebM, OGV (Max: 50MB)</small>
                        </div>
                        <input type="file" accept="video/*" style="display: none;">
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn-cancel" style="padding: 0.5rem 1rem; margin-right: 0.5rem; border: 1px solid #dee2e6; background: white; border-radius: 4px; cursor: pointer;">Cancel</button>
                    <button type="button" class="btn-insert" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Insert Video</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Tab switching
        const tabButtons = modal.querySelectorAll('.tab-btn');
        const tabPanels = modal.querySelectorAll('.tab-panel');
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => {
                    b.classList.remove('active');
                    b.style.borderBottomColor = 'transparent';
                });
                btn.classList.add('active');
                btn.style.borderBottomColor = '#007bff';
                
                tabPanels.forEach(panel => panel.style.display = 'none');
                modal.querySelector(`#${btn.dataset.tab}-panel`).style.display = 'block';
            });
        });
        
        // Video URL embedding
        const urlInput = modal.querySelector('.video-url');
        const widthInput = modal.querySelector('.video-width');
        const heightInput = modal.querySelector('.video-height');
        const insertBtn = modal.querySelector('.btn-insert');
        const cancelBtn = modal.querySelector('.btn-cancel');
        
        // File upload handling
        const uploadArea = modal.querySelector('.upload-area');
        const fileInput = modal.querySelector('input[type="file"]');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            uploadArea.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff;"></i><p style="margin-top: 1rem; color: #007bff;">Uploading video...</p>';
            
            // For now, show a message that video upload needs backend implementation
            setTimeout(() => {
                uploadArea.innerHTML = '<i class="fas fa-info-circle" style="font-size: 2rem; color: #ffc107;"></i><p style="margin-top: 1rem; color: #856404;">Video upload feature requires additional server configuration. Please use YouTube/Vimeo embed instead.</p>';
            }, 1000);
        });
        
        insertBtn.addEventListener('click', () => {
            const activePanel = modal.querySelector('.tab-panel:not([style*="display: none"])');
            
            if (activePanel.id === 'embed-panel') {
                const url = urlInput.value.trim();
                const width = widthInput.value || '560';
                const height = heightInput.value || '315';
                
                if (url) {
                    let embedHTML = '';
                    
                    // YouTube
                    if (url.includes('youtube.com') || url.includes('youtu.be')) {
                        const videoId = this.extractYouTubeId(url);
                        if (videoId) {
                            embedHTML = `<div class="video-container" style="position: relative; width: 100%; max-width: ${width}px; margin: 15px 0;"><iframe width="${width}" height="${height}" src="https://www.youtube.com/embed/${videoId}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width: 100%; height: auto; aspect-ratio: 16/9;"></iframe></div>`;
                        }
                    }
                    // Vimeo
                    else if (url.includes('vimeo.com')) {
                        const videoId = this.extractVimeoId(url);
                        if (videoId) {
                            embedHTML = `<div class="video-container" style="position: relative; width: 100%; max-width: ${width}px; margin: 15px 0;"><iframe src="https://player.vimeo.com/video/${videoId}" width="${width}" height="${height}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="width: 100%; height: auto; aspect-ratio: 16/9;"></iframe></div>`;
                        }
                    }
                    // Direct video URL
                    else {
                        embedHTML = `<div class="video-container" style="position: relative; width: 100%; max-width: ${width}px; margin: 15px 0;"><video controls width="${width}" height="${height}" style="width: 100%; height: auto;"><source src="${url}" type="video/mp4">Your browser does not support the video tag.</video></div>`;
                    }
                    
                    if (embedHTML) {
                        document.execCommand('insertHTML', false, embedHTML);
                        document.body.removeChild(modal);
                    } else {
                        alert('Please enter a valid YouTube, Vimeo, or direct video URL');
                    }
                } else {
                    alert('Please enter a video URL');
                }
            }
        });
        
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }

    extractYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    extractVimeoId(url) {
        const regExp = /vimeo\.com\/(?:.*#|.*\/videos\/)?([0-9]+)/;
        const match = url.match(regExp);
        return match ? match[1] : null;
    }

    insertTable() {
        const rows = prompt('Number of rows:', '3');
        const cols = prompt('Number of columns:', '3');
        
        if (rows && cols) {
            let tableHTML = '<table class="table table-bordered"><tbody>';
            
            for (let i = 0; i < parseInt(rows); i++) {
                tableHTML += '<tr>';
                for (let j = 0; j < parseInt(cols); j++) {
                    tableHTML += i === 0 ? '<th>Header</th>' : '<td>Cell</td>';
                }
                tableHTML += '</tr>';
            }
            
            tableHTML += '</tbody></table>';
            document.execCommand('insertHTML', false, tableHTML);
        }
    }

    toggleSourceMode() {
        if (this.sourceMode) {
            // Switch back to visual mode
            const sourceText = this.content.textContent;
            this.content.innerHTML = sourceText;
            this.content.contentEditable = true;
            this.sourceMode = false;
        } else {
            // Switch to source mode
            const htmlContent = this.content.innerHTML;
            this.content.textContent = htmlContent;
            this.content.contentEditable = true;
            this.sourceMode = true;
        }
    }

    updateToolbarState() {
        // Update button active states
        const buttons = this.toolbar.querySelectorAll('.editor-btn[data-command]');
        buttons.forEach(btn => {
            const command = btn.getAttribute('data-command');
            if (['bold', 'italic', 'underline', 'strikeThrough'].includes(command)) {
                btn.classList.toggle('active', document.queryCommandState(command));
            }
        });

        // Update format select
        const formatSelect = this.toolbar.querySelector('select[data-command="formatBlock"]');
        if (formatSelect) {
            const format = document.queryCommandValue('formatBlock').toLowerCase();
            formatSelect.value = format || 'p';
        }
    }

    getContent() {
        return this.content.innerHTML;
    }

    setContent(html) {
        this.content.innerHTML = html;
    }

    save() {
        // Override this method to implement auto-save functionality
        if (this.options.onSave) {
            this.options.onSave(this.getContent());
        }
    }
}

// Media Upload Handler
class MediaUploader {
    constructor(options = {}) {
        this.options = {
            maxSize: 5 * 1024 * 1024, // 5MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            ...options
        };
    }

    createUploadArea(container) {
        const uploadArea = document.createElement('div');
        uploadArea.className = 'media-upload-area';
        uploadArea.innerHTML = `
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="upload-text">Click or drag files to upload</div>
            <div class="upload-hint">Maximum file size: ${this.options.maxSize / (1024 * 1024)}MB</div>
            <input type="file" style="display: none;" accept="${this.options.allowedTypes.join(',')}" multiple>
        `;

        const fileInput = uploadArea.querySelector('input[type="file"]');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            this.handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        container.appendChild(uploadArea);
        return uploadArea;
    }

    handleFiles(files) {
        Array.from(files).forEach(file => {
            if (this.validateFile(file)) {
                this.uploadFile(file);
            }
        });
    }

    validateFile(file) {
        if (!this.options.allowedTypes.includes(file.type)) {
            alert(`File type ${file.type} is not allowed`);
            return false;
        }

        if (file.size > this.options.maxSize) {
            alert(`File size exceeds ${this.options.maxSize / (1024 * 1024)}MB limit`);
            return false;
        }

        return true;
    }

    uploadFile(file) {
        // Create a simple base64 preview for now
        // In production, you'd upload to your server
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = `<img src="${e.target.result}" alt="${file.name}" style="max-width: 100%; height: auto; border-radius: 4px; margin: 10px 0;">`;
            
            // Insert into editor if one is focused
            const activeEditor = document.querySelector('.editor-content:focus');
            if (activeEditor) {
                document.execCommand('insertHTML', false, img);
            }
        };
        reader.readAsDataURL(file);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize editors
    const editorContainers = document.querySelectorAll('[data-editor]');
    editorContainers.forEach(container => {
        const editor = new ProfessionalEditor(container.id, {
            height: container.dataset.height || '400px',
            autoSave: container.dataset.autosave === 'true'
        });
        
        // Load existing content if available (for edit mode)
        const hiddenTextarea = document.getElementById('finalContent');
        if (hiddenTextarea && hiddenTextarea.value.trim()) {
            // Delay setting content to ensure editor is fully initialized
            setTimeout(() => {
                editor.setContent(hiddenTextarea.value);
            }, 100);
        }
    });

    // Initialize media uploader
    window.mediaUploader = new MediaUploader();
});