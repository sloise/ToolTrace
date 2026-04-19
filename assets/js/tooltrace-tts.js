/**
 * ToolTrace — Text-to-speech (Web Speech API).
 * Fix: cancel() and speak() must NOT run in the same synchronous tick on Chromium —
 * the browser silently drops the utterance. We always defer speak() with setTimeout(0)
 * after a cancel(), and we wait for voices to be populated before speaking.
 */
(function () {
    'use strict';

    function pickEnglishVoice(voices) {
        if (!voices || !voices.length) return null;
        var i, v;
        for (i = 0; i < voices.length; i++) {
            v = voices[i];
            if ((v.lang || '').toLowerCase().indexOf('en') === 0) return v;
        }
        return voices[0];
    }

    /**
     * Actually build and fire the utterance.
     * MUST be called after cancel() has already been issued in a previous tick.
     */
    function fireUtterance(text) {
        var s = window.speechSynthesis;
        if (!s) return;

        var u = new SpeechSynthesisUtterance(text);
        u.rate   = 0.95;
        u.pitch  = 1;
        u.volume = 1;
        u.lang   = 'en-US';

        var voices = s.getVoices();
        var voice  = pickEnglishVoice(voices);
        if (voice) u.voice = voice;

        u.onstart = function () {
            try { if (s.paused) s.resume(); } catch (e) {}
        };

        u.onerror = function (ev) {
            if (ev && ev.error && ev.error !== 'canceled' && ev.error !== 'interrupted') {
                console.warn('ToolTrace TTS error:', ev.error);
            }
        };

        try {
            s.speak(u);
        } catch (err) {
            console.warn('ToolTrace TTS speak() threw:', err);
        }
    }

    /**
     * Cancel any current speech, then speak `text` after a browser-safe delay.
     * The delay is the key fix: Chromium drops an utterance that is queued in the
     * same synchronous tick as cancel().
     */
    function doSpeak(text) {
        var s = window.speechSynthesis;
        if (!s) return;

        try { s.cancel(); } catch (e) {}

        // Wait one tick so the cancel has fully settled before we speak.
        window.setTimeout(function () {
            fireUtterance(text);
        }, 50);
    }

    /**
     * Public API.
     * Waits for voices to be available (needed on first call in some browsers),
     * then delegates to doSpeak().
     */
    window.tooltraceSpeak = function (text) {
        var s = window.speechSynthesis;
        if (!s) {
            console.warn('ToolTrace TTS: Web Speech API not supported in this browser.');
            return;
        }

        var t = String(text == null ? '' : text).trim();
        if (!t) {
            console.warn('ToolTrace TTS: no text provided.');
            return;
        }

        var voices = s.getVoices();
        if (voices && voices.length > 0) {
            doSpeak(t);
            return;
        }

        // Voices not loaded yet — wait for the voiceschanged event, with a fallback timeout.
        var done = false;

        function proceed() {
            if (done) return;
            done = true;
            try { s.removeEventListener('voiceschanged', onVoicesChanged); } catch (e) {}
            doSpeak(t);
        }

        function onVoicesChanged() {
            if (s.getVoices().length > 0) proceed();
        }

        s.addEventListener('voiceschanged', onVoicesChanged);
        s.getVoices(); // trigger population in some browsers

        // Fallback: if voiceschanged never fires (e.g. Firefox with no voices), speak anyway.
        window.setTimeout(proceed, 500);
    };

    // Alias for any legacy callers.
    window.speakText = window.tooltraceSpeak;

    // Optional test helper — call tooltraceSpeak_test() from the browser console.
    window.tooltraceSpeak_test = function () {
        window.tooltraceSpeak('ToolTrace text-to-speech is working correctly.');
    };

    // Declarative usage: <button data-tooltrace-speak="Hello world">🔊</button>
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (ev) {
            var el = ev.target && ev.target.closest('[data-tooltrace-speak]');
            if (!el || el.disabled) return;
            var raw = el.getAttribute('data-tooltrace-speak');
            if (raw == null || raw === '') return;
            window.tooltraceSpeak(raw);
        });
    });

})();