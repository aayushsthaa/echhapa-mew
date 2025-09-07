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
        const url = prompt('Enter image URL:', 'https://');
        if (url) {
            const alt = prompt('Enter alt text (optional):', '');
            const img = `<img src="${url}" alt="${alt}" style="max-width: 100%; height: auto;">`;
            document.execCommand('insertHTML', false, img);
        }
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
        new ProfessionalEditor(container.id, {
            height: container.dataset.height || '400px',
            autoSave: container.dataset.autosave === 'true'
        });
    });

    // Initialize media uploader
    window.mediaUploader = new MediaUploader();
});