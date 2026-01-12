@extends('layouts.platform')

@section('content')
{{-- Create Support Ticket --}}

<div class="max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="{{ route('modules.support.index') }}" class="text-sm opacity-70 hover:opacity-100 mr-4">← Back</a>
        <h1 class="text-2xl font-bold">New Support Ticket</h1>
    </div>

    @if($errors->any())
    <div class="mb-4 p-3 rounded-md text-sm" style="background-color: var(--status-error-color); color: white;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="rounded-lg p-6" style="background-color: var(--card-background-color);">
        <form method="POST" action="{{ route('modules.support.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Category') }}</label>
                <select name="ticket_category" 
                        required
                        class="w-full px-4 py-2 rounded-md border-0"
                        style="background-color: var(--content-background-color); color: var(--content-text-color);">
                    <option value="">{{ __translator('Select a category...') }}</option>
                    @foreach(\App\Models\SupportTicket::subjectCategories() as $value => $label)
                    <option value="{{ $value }}" {{ old('ticket_category') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Subject') }}</label>
                <input type="text" 
                       name="ticket_subject_line" 
                       value="{{ old('ticket_subject_line') }}"
                       class="w-full px-4 py-2 rounded-md border-0"
                       style="background-color: var(--content-background-color); color: var(--content-text-color);"
                       placeholder="{{ __translator('Brief description of your issue') }}"
                       required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">{{ __translator('Description') }}</label>
                <textarea name="ticket_description_body" 
                          rows="8"
                          class="w-full px-4 py-2 rounded-md border-0"
                          style="background-color: var(--content-background-color); color: var(--content-text-color);"
                          placeholder="{{ __translator('Please provide as much detail as possible...') }}"
                          required>{{ old('ticket_description_body') }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">{{ __translator('Attachments') }} <span class="opacity-50">({{ __translator('optional') }})</span></label>
                
                {{-- Drag & Drop File Upload Zone --}}
                <div id="dropzone" 
                     class="relative rounded-lg border-2 border-dashed p-6 text-center cursor-pointer transition-all duration-200"
                     style="background-color: var(--content-background-color); border-color: var(--sidebar-hover-background-color);"
                     ondragover="handleDragOver(event)" 
                     ondragleave="handleDragLeave(event)" 
                     ondrop="handleDrop(event)"
                     onclick="document.getElementById('file-input').click()">
                    
                    <input type="file" 
                           id="file-input"
                           name="attachments[]" 
                           multiple
                           class="hidden"
                           accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip"
                           onchange="handleFileSelect(this.files)">
                    
                    <div id="dropzone-content" class="pointer-events-none">
                        <svg class="mx-auto h-12 w-12 opacity-40 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm font-medium mb-1">{{ __translator('Drag & drop files here') }}</p>
                        <p class="text-xs opacity-50">{{ __translator('or click to browse') }}</p>
                    </div>
                </div>
                
                {{-- File List Preview --}}
                <div id="file-list" class="mt-3 space-y-2 hidden"></div>
                
                <p class="text-xs opacity-50 mt-2">{{ __translator('Max 5 files. Allowed: images, PDF, DOC, TXT, ZIP (max 10MB each)') }}</p>
            </div>

            <script>
                let selectedFiles = [];
                const maxFiles = 5;
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                                      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                      'text/plain', 'application/zip', 'application/x-zip-compressed'];
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];

                function handleDragOver(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropzone = document.getElementById('dropzone');
                    dropzone.style.borderColor = 'var(--brand-primary-color)';
                    dropzone.style.backgroundColor = 'var(--sidebar-hover-background-color)';
                }

                function handleDragLeave(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropzone = document.getElementById('dropzone');
                    dropzone.style.borderColor = 'var(--sidebar-hover-background-color)';
                    dropzone.style.backgroundColor = 'var(--content-background-color)';
                }

                function handleDrop(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleDragLeave(e);
                    const files = e.dataTransfer.files;
                    handleFileSelect(files);
                }

                function handleFileSelect(files) {
                    for (let i = 0; i < files.length; i++) {
                        if (selectedFiles.length >= maxFiles) {
                            alert('Maximum ' + maxFiles + ' files allowed.');
                            break;
                        }
                        
                        const file = files[i];
                        const ext = file.name.split('.').pop().toLowerCase();
                        
                        if (!allowedExtensions.includes(ext)) {
                            alert('File type not allowed: ' + file.name);
                            continue;
                        }
                        
                        if (file.size > maxSize) {
                            alert('File too large (max 10MB): ' + file.name);
                            continue;
                        }
                        
                        // Check for duplicates
                        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                            continue;
                        }
                        
                        selectedFiles.push(file);
                    }
                    
                    updateFileList();
                    updateFileInput();
                }

                function removeFile(index) {
                    selectedFiles.splice(index, 1);
                    updateFileList();
                    updateFileInput();
                }

                function updateFileList() {
                    const fileList = document.getElementById('file-list');
                    
                    if (selectedFiles.length === 0) {
                        fileList.classList.add('hidden');
                        fileList.innerHTML = '';
                        return;
                    }
                    
                    fileList.classList.remove('hidden');
                    fileList.innerHTML = selectedFiles.map((file, index) => {
                        const size = formatFileSize(file.size);
                        const icon = getFileIcon(file.name);
                        return `
                            <div class="flex items-center justify-between p-3 rounded-md" style="background-color: var(--content-background-color);">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="text-lg flex-shrink-0">${icon}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate">${escapeHtml(file.name)}</p>
                                        <p class="text-xs opacity-50">${size}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="removeFile(${index})" 
                                        class="flex-shrink-0 p-1 rounded hover:opacity-70 transition-opacity"
                                        style="color: var(--status-error-color);">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    }).join('');
                }

                function updateFileInput() {
                    const input = document.getElementById('file-input');
                    const dataTransfer = new DataTransfer();
                    selectedFiles.forEach(file => dataTransfer.items.add(file));
                    input.files = dataTransfer.files;
                }

                function formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                }

                function getFileIcon(filename) {
                    const ext = filename.split('.').pop().toLowerCase();
                    const icons = {
                        'pdf': '📄',
                        'doc': '📝', 'docx': '📝',
                        'txt': '📃',
                        'zip': '📦',
                        'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
                    };
                    return icons[ext] || '📎';
                }

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }
            </script>

            <div class="flex gap-3">
                <button type="submit" 
                        class="px-6 py-2 rounded-md font-medium"
                        style="background-color: var(--brand-primary-color); color: var(--button-text-color);">
                    {{ __translator('Submit Ticket') }}
                </button>
                <a href="{{ route('modules.support.index') }}" 
                   class="px-6 py-2 rounded-md font-medium"
                   style="background-color: var(--sidebar-hover-background-color);">
                    {{ __translator('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
