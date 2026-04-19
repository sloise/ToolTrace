/**
 * ToolTrace — Web Speech API helper for voice search (Chrome/Edge/Safari).
 * Requires microphone permission. Use https:// or http://localhost.
 */
(function () {
    'use strict';

    window.tooltraceInitVoiceSearch = function (options) {
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        var mic = document.getElementById(options.micId || 'micBtn');
        var input = document.getElementById(options.inputId || 'searchInput');
        if (!mic || !input) return;

        if (!SR) {
            mic.title = 'Voice search is not supported in this browser.';
            mic.setAttribute('aria-disabled', 'true');
            return;
        }

        var recognition = new SR();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = options.lang || 'en-US';

        var form = options.formId ? document.getElementById(options.formId) : null;
        var statusEl = options.statusId ? document.getElementById(options.statusId) : null;
        var listening = false;

        function setUi(active) {
            listening = active;
            mic.classList.toggle('listening', active);
            if (statusEl) statusEl.classList.toggle('active', active);
            mic.style.opacity = active ? '0.85' : '1';
        }

        mic.addEventListener('click', function () {
            if (listening) {
                try {
                    recognition.abort();
                } catch (e) {}
                return;
            }
            try {
                recognition.start();
            } catch (e) {
                try {
                    recognition.start();
                } catch (e2) {
                    alert('Could not start voice recognition. Try again.');
                }
            }
        });

        recognition.onstart = function () {
            setUi(true);
        };

        recognition.onend = function () {
            setUi(false);
        };

        recognition.onresult = function (event) {
            var transcript = '';
            for (var i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            transcript = transcript.trim();
            input.value = transcript;
            if (typeof options.onText === 'function') {
                options.onText(transcript);
            } else if (form) {
                form.submit();
            }
        };

        recognition.onerror = function (event) {
            setUi(false);
            if (event.error === 'not-allowed') {
                alert('Microphone is blocked. Click the lock icon in the address bar and allow the microphone for this site.');
            } else if (event.error === 'no-speech') {
                /* ignore */
            } else if (event.error !== 'aborted') {
                console.warn('Speech recognition:', event.error);
            }
        };
    };
})();
