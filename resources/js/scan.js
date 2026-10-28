// Staff scan screen: submit a ticket as soon as the scanner has typed it, no Enter or click needed.
//
// A keyboard-mode QR scanner "types" the whole token in a few milliseconds.
//  1. A complete token (40 hex characters, see tickets.token) is submitted at once.
//  2. Anything else typed in one fast burst (a person is much slower) is submitted
//     once the typing pauses, so an unknown QR still gets an "invalid ticket" answer.
// Typing a token by hand and pressing Enter keeps working as before.

const TOKEN = /^[0-9a-f]{40}$/i;
const MAX_KEY_GAP_MS = 50;
const MIN_BURST_CHARS = 8;
const SETTLE_MS = 150;

export function initScanAutoSubmit(input) {
    const form = input.form;
    let lastKeyAt = 0;
    let burst = 0;
    let timer = null;
    let sent = false;

    const submit = () => {
        clearTimeout(timer);
        if (sent || input.value.trim() === '') return;
        sent = true;
        form.requestSubmit();
    };

    input.addEventListener('input', () => {
        const now = performance.now();
        burst = now - lastKeyAt < MAX_KEY_GAP_MS ? burst + 1 : 1;
        lastKeyAt = now;
        clearTimeout(timer);

        if (TOKEN.test(input.value.trim())) {
            submit();
        } else if (burst >= MIN_BURST_CHARS) {
            timer = setTimeout(submit, SETTLE_MS);
        }
    });

    // Enter from the scanner (or the keyboard) submits through the form as usual; never twice.
    form.addEventListener('submit', () => {
        sent = true;
        clearTimeout(timer);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('[data-scan-input]');

    if (input?.form) {
        initScanAutoSubmit(input);
    }
});
