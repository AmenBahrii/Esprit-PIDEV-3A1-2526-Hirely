(function () {
    const uploaders = document.querySelectorAll('[data-cloudinary-upload]');

    if (!uploaders.length) {
        return;
    }

    uploaders.forEach((uploader) => {
        const enabled = 'true' === uploader.dataset.cloudinaryEnabled;
        const provider = uploader.dataset.cloudinaryProvider || 'local';
        const cloudName = uploader.dataset.cloudinaryCloudName || '';
        const unsignedPreset = uploader.dataset.cloudinaryUnsignedPreset || '';
        const uploadUrl = uploader.dataset.cloudinaryUploadUrl || '';
        const fileInput = uploader.querySelector('[data-cloudinary-file-input]');
        const uploadButton = uploader.querySelector('[data-cloudinary-upload-button]');
        const clearButton = uploader.querySelector('[data-cloudinary-clear-button]');
        const status = uploader.querySelector('[data-cloudinary-status]');
        const current = uploader.querySelector('[data-cloudinary-current]');
        const nameNode = uploader.querySelector('[data-cloudinary-name]');
        const dropzone = uploader.querySelector('[data-cloudinary-dropzone]');
        const selectedFileNode = uploader.querySelector('[data-cloudinary-selected-file]');
        const form = uploader.closest('form');

        if (!fileInput || !uploadButton || !clearButton || !status || !current || !nameNode || !form) {
            return;
        }

        const selectFileText = uploader.dataset.attachmentSelectFile || 'Select any file from your device to attach it.';
        const selectedFilePrefix = uploader.dataset.attachmentSelectedFilePrefix || 'Selected file:';
        const chooseBeforeText = uploader.dataset.attachmentChooseBefore || 'Choose a file before starting the upload.';
        const uploadingText = uploader.dataset.attachmentUploading || 'Uploading attachment...';
        const uploadSuccessText = uploader.dataset.attachmentUploadSuccess || 'Attachment uploaded successfully.';
        const clearedText = uploader.dataset.attachmentCleared || 'Attachment cleared.';
        const clearedReplaceText = uploader.dataset.attachmentClearedReplace || 'Attachment cleared. You can upload a new file.';
        const noAttachmentText = uploader.dataset.attachmentNone || 'No attachment uploaded yet.';
        const openFileText = uploader.dataset.attachmentOpenFile || 'Open file';

        const filePathField = form.querySelector('[data-cloudinary-file-path]');
        const publicIdField = form.querySelector('[data-cloudinary-public-id]');
        const originalNameField = form.querySelector('[data-cloudinary-original-name]');
        const contentTypeField = form.querySelector('[data-cloudinary-content-type]');

        if (!filePathField || !publicIdField || !originalNameField || !contentTypeField) {
            return;
        }

        const setStatus = (message, isError) => {
            status.textContent = message;
            status.classList.toggle('attachment-status-error', !!isError);
        };

        const updateSelectedFile = (file) => {
            if (!selectedFileNode) {
                return;
            }

            selectedFileNode.textContent = file
                ? `${selectedFilePrefix} ${file.name}`
                : selectFileText;
            selectedFileNode.classList.toggle('has-file', !!file);
            uploader.classList.toggle('has-pending-file', !!file);
        };

        const getPreviewKind = (url, contentType, fileName) => {
            const normalizedType = (contentType || '').toLowerCase();
            const source = (fileName || url || '').toLowerCase();

            if (normalizedType.startsWith('image/') || /\.(png|jpe?g|gif|webp|bmp|svg)$/.test(source)) {
                return 'image';
            }

            if ('application/pdf' === normalizedType || /\.pdf$/.test(source)) {
                return 'pdf';
            }

            if (normalizedType.startsWith('video/') || /\.(mp4|webm|mov|avi|mkv)$/.test(source)) {
                return 'video';
            }

            if (normalizedType.startsWith('audio/') || /\.(mp3|wav|ogg|m4a)$/.test(source)) {
                return 'audio';
            }

            return 'file';
        };

        const buildPreviewNode = (url, fileName, contentType) => {
            const wrapper = document.createElement('div');
            const previewKind = getPreviewKind(url, contentType, fileName);

            wrapper.className = 'attachment-preview-inline attachment-preview-' + previewKind;

            if ('image' === previewKind) {
                const image = document.createElement('img');
                image.src = url;
                image.alt = fileName || 'Attachment preview';
                image.className = 'attachment-preview-media';
                wrapper.appendChild(image);

                return wrapper;
            }

            if ('pdf' === previewKind) {
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.title = fileName || 'Attachment preview';
                frame.className = 'attachment-preview-media attachment-preview-frame';
                wrapper.appendChild(frame);

                return wrapper;
            }

            if ('video' === previewKind) {
                const video = document.createElement('video');
                video.controls = true;
                video.preload = 'metadata';
                video.className = 'attachment-preview-media';
                const source = document.createElement('source');
                source.src = url;
                video.appendChild(source);
                wrapper.appendChild(video);

                return wrapper;
            }

            if ('audio' === previewKind) {
                const audio = document.createElement('audio');
                audio.controls = true;
                audio.preload = 'metadata';
                audio.className = 'attachment-preview-audio';
                const source = document.createElement('source');
                source.src = url;
                audio.appendChild(source);
                wrapper.appendChild(audio);

                return wrapper;
            }

            const fileBox = document.createElement('div');
            fileBox.className = 'attachment-preview-file';

            const icon = document.createElement('span');
            icon.className = 'attachment-preview-icon';
            icon.textContent = 'File';

            const label = document.createElement('span');
            label.className = 'attachment-preview-name';
            label.textContent = fileName || 'Uploaded file';

            fileBox.append(icon, label);
            wrapper.appendChild(fileBox);

            return wrapper;
        };

        const updateCurrentAttachment = (url, fileName, contentType) => {
            current.classList.toggle('empty', !url);

            current.innerHTML = '';

            if (!url) {
                nameNode.textContent = 'No attachment uploaded yet.';
                current.appendChild(nameNode);
                return;
            }

            const body = document.createElement('div');
            body.className = 'attachment-current-body';

            const preview = buildPreviewNode(url, fileName, contentType);

            const meta = document.createElement('div');
            meta.className = 'attachment-current-meta';

            const link = document.createElement('a');
            link.className = 'attachment-link';
            link.target = '_blank';
            link.rel = 'noreferrer';
            link.dataset.cloudinaryLink = '';
            link.href = url;
            link.textContent = openFileText;

            nameNode.textContent = fileName || 'Attachment uploaded';
            meta.append(nameNode, link);
            body.append(preview, meta);
            current.appendChild(body);
        };

        const clearAttachment = () => {
            fileInput.value = '';
            filePathField.value = '';
            publicIdField.value = '';
            originalNameField.value = '';
            contentTypeField.value = '';
            updateCurrentAttachment('', '', '');
            updateSelectedFile(null);
            setStatus(enabled
                ? clearedReplaceText
                : clearedText, false);
        };

        const getUploadEndpoint = (file) => {
            const contentType = (file.type || '').toLowerCase();

            if (contentType.startsWith('image/')) {
                return 'image';
            }

            if (contentType.startsWith('video/') || contentType.startsWith('audio/')) {
                return 'video';
            }

            return 'raw';
        };

        const uploadWithLocalApp = async (selectedFile) => {
            if (!uploadUrl) {
                throw new Error('File upload is not configured right now.');
            }

            const formData = new FormData();
            formData.append('file', selectedFile);

            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok || !payload.secure_url) {
                throw new Error(payload.error?.message || 'Upload failed.');
            }

            return payload;
        };

        const uploadWithCloudinary = async (selectedFile) => {
            if (!enabled || !cloudName || !unsignedPreset) {
                throw new Error('Cloud upload is not configured right now.');
            }

            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('upload_preset', unsignedPreset);
            formData.append('folder', 'hirely/tasks');

            const endpointType = getUploadEndpoint(selectedFile);
            const response = await fetch(`https://api.cloudinary.com/v1_1/${cloudName}/${endpointType}/upload`, {
                method: 'POST',
                body: formData,
            });

            const payload = await response.json();

            if (!response.ok || !payload.secure_url) {
                throw new Error(payload.error?.message || 'Upload failed.');
            }

            return payload;
        };

        uploadButton.addEventListener('click', async () => {
            const selectedFile = fileInput.files && fileInput.files[0];

            if (!selectedFile) {
                setStatus(chooseBeforeText, true);
                return;
            }

            uploadButton.disabled = true;
            setStatus(uploadingText, false);

            try {
                const payload = 'cloudinary' === provider
                    ? await uploadWithCloudinary(selectedFile)
                    : await uploadWithLocalApp(selectedFile);

                filePathField.value = payload.secure_url || '';
                publicIdField.value = payload.public_id || '';
                originalNameField.value = payload.original_filename
                    ? payload.original_filename + (payload.format ? '.' + payload.format : '')
                    : (selectedFile.name || '');
                contentTypeField.value = selectedFile.type || payload.content_type || payload.resource_type || '';

                updateCurrentAttachment(
                    payload.secure_url || '',
                    originalNameField.value || 'attachment',
                    contentTypeField.value || ''
                );
                updateSelectedFile(selectedFile);
                setStatus(uploadSuccessText, false);
            } catch (error) {
                setStatus(error.message || 'Attachment upload failed.', true);
            } finally {
                uploadButton.disabled = false;
            }
        });

        fileInput.addEventListener('change', () => {
            const selectedFile = fileInput.files && fileInput.files[0];
            updateSelectedFile(selectedFile || null);

            if (selectedFile) {
                setStatus(`${selectedFilePrefix} ${selectedFile.name}`, false);
            }
        });

        if (dropzone) {
            ['dragenter', 'dragover'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend', 'drop'].forEach((eventName) => {
                dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    if ('dragleave' === eventName && dropzone.contains(event.relatedTarget)) {
                        return;
                    }
                    dropzone.classList.remove('is-dragover');
                });
            });

            dropzone.addEventListener('drop', (event) => {
                const files = event.dataTransfer && event.dataTransfer.files;
                if (!files || !files.length) {
                    return;
                }

                fileInput.files = files;
                const selectedFile = files[0];
                updateSelectedFile(selectedFile);
                setStatus(`${selectedFilePrefix} ${selectedFile.name}`, false);
            });
        }

        clearButton.addEventListener('click', clearAttachment);

        if (filePathField.value && originalNameField.value) {
            updateCurrentAttachment(filePathField.value, originalNameField.value, contentTypeField.value);
        }

        updateSelectedFile(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null);
        if (!filePathField.value) {
            nameNode.textContent = noAttachmentText;
        }
    });
})();
