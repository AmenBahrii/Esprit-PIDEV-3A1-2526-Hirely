(function () {
    const FACE_API_SCRIPT = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
    const FACE_MODELS_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
    let scriptPromise;
    let modelsPromise;

    async function ensureScriptLoaded() {
        if (window.faceapi) {
            return;
        }

        if (!scriptPromise) {
            scriptPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = FACE_API_SCRIPT;
                script.async = true;
                script.onload = resolve;
                script.onerror = () => reject(new Error('Unable to load face recognition library.'));
                document.head.appendChild(script);
            });
        }

        await scriptPromise;
    }

    async function ensureModelsLoaded() {
        await ensureScriptLoaded();

        if (!modelsPromise) {
            modelsPromise = Promise.all([
                window.faceapi.nets.tinyFaceDetector.loadFromUri(FACE_MODELS_URL),
                window.faceapi.nets.faceLandmark68Net.loadFromUri(FACE_MODELS_URL),
                window.faceapi.nets.faceRecognitionNet.loadFromUri(FACE_MODELS_URL),
            ]);
        }

        await modelsPromise;
    }

    async function startCamera(video) {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user' },
            audio: false,
        });

        video.srcObject = stream;
        await video.play();

        return stream;
    }

    function stopCamera(video) {
        const stream = video.srcObject;
        if (stream && typeof stream.getTracks === 'function') {
            stream.getTracks().forEach((track) => track.stop());
        }
        video.srcObject = null;
    }

    async function captureDescriptor(video) {
        await ensureModelsLoaded();

        const detection = await window.faceapi
            .detectSingleFace(video, new window.faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            throw new Error('No face was detected. Center your face and try again.');
        }

        return Array.from(detection.descriptor);
    }

    function setMessage(element, message, tone) {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.dataset.tone = tone || 'neutral';
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        return response.json();
    }

    function initFaceLogin() {
        const root = document.querySelector('[data-face-login]');
        if (!root) {
            return;
        }

        const video = root.querySelector('video');
        const email = root.querySelector('[data-face-email]');
        const startButton = root.querySelector('[data-face-start]');
        const verifyButton = root.querySelector('[data-face-verify]');
        const status = root.querySelector('[data-face-status]');

        if (!video || !email || !startButton || !verifyButton) {
            return;
        }

        startButton.addEventListener('click', async function () {
            try {
                setMessage(status, 'Starting camera and loading face models...', 'neutral');
                await startCamera(video);
                await ensureModelsLoaded();
                setMessage(status, 'Camera ready. Verify when your face is clearly visible.', 'success');
            } catch (error) {
                setMessage(status, error.message || 'Unable to start camera.', 'error');
            }
        });

        verifyButton.addEventListener('click', async function () {
            try {
                if (!email.value.trim()) {
                    setMessage(status, 'Enter your email before using face sign-in.', 'error');
                    return;
                }

                setMessage(status, 'Scanning face...', 'neutral');
                const descriptor = await captureDescriptor(video);
                const result = await postJson(root.dataset.loginUrl, {
                    email: email.value.trim(),
                    descriptor: descriptor,
                    _token: root.dataset.csrf,
                });

                if (!result.success) {
                    setMessage(status, result.message || 'Face sign-in failed.', 'error');
                    return;
                }

                setMessage(status, 'Face verified. Redirecting...', 'success');
                window.location.href = result.redirect;
            } catch (error) {
                setMessage(status, error.message || 'Unable to verify face.', 'error');
            }
        });

        window.addEventListener('beforeunload', function () {
            stopCamera(video);
        });
    }

    function initFaceEnrollment() {
        const root = document.querySelector('[data-face-enroll]');
        if (!root) {
            return;
        }

        const video = root.querySelector('video');
        const startButton = root.querySelector('[data-enroll-start]');
        const saveButton = root.querySelector('[data-enroll-save]');
        const removeButton = root.querySelector('[data-enroll-remove]');
        const status = root.querySelector('[data-enroll-status]');
        const badge = document.querySelector('[data-face-badge]');

        if (!video || !startButton || !saveButton || !removeButton) {
            return;
        }

        startButton.addEventListener('click', async function () {
            try {
                setMessage(status, 'Starting camera and loading face models...', 'neutral');
                await startCamera(video);
                await ensureModelsLoaded();
                setMessage(status, 'Camera ready. Keep your face centered, then save.', 'success');
            } catch (error) {
                setMessage(status, error.message || 'Unable to start camera.', 'error');
            }
        });

        saveButton.addEventListener('click', async function () {
            try {
                setMessage(status, 'Capturing your face descriptor...', 'neutral');
                const descriptor = await captureDescriptor(video);
                const result = await postJson(root.dataset.enrollUrl, {
                    descriptor: descriptor,
                    _token: root.dataset.enrollCsrf,
                });

                if (!result.success) {
                    setMessage(status, result.message || 'Unable to save Face ID.', 'error');
                    return;
                }

                if (badge) {
                    badge.textContent = 'Enrolled';
                }

                setMessage(status, result.message || 'Face ID saved successfully.', 'success');
            } catch (error) {
                setMessage(status, error.message || 'Unable to save Face ID.', 'error');
            }
        });

        removeButton.addEventListener('click', async function () {
            try {
                const result = await postJson(root.dataset.removeUrl, {
                    _token: root.dataset.removeCsrf,
                });

                if (!result.success) {
                    setMessage(status, result.message || 'Unable to remove Face ID.', 'error');
                    return;
                }

                if (badge) {
                    badge.textContent = 'Not enrolled';
                }

                setMessage(status, result.message || 'Face ID removed.', 'success');
            } catch (error) {
                setMessage(status, error.message || 'Unable to remove Face ID.', 'error');
            }
        });

        window.addEventListener('beforeunload', function () {
            stopCamera(video);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initFaceLogin();
            initFaceEnrollment();
        });
    } else {
        initFaceLogin();
        initFaceEnrollment();
    }
})();
