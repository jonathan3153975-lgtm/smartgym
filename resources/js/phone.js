/**
 * Brazilian phone mask shared by every form that collects a phone number.
 *
 * Usage:
 *   <input type="text" data-phone>
 */

function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatPhone(value) {
    const digits = onlyDigits(value).slice(0, 11);

    if (!digits) {
        return '';
    }

    if (digits.length <= 10) {
        return digits
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
}

export function initPhoneMasks(root = document) {
    root.querySelectorAll('input[data-phone]').forEach((input) => {
        if (input.dataset.phoneInitialized === 'true') {
            return;
        }

        input.dataset.phoneInitialized = 'true';
        input.setAttribute('inputmode', 'tel');
        input.setAttribute('maxlength', '15');

        const applyMask = () => {
            input.value = formatPhone(input.value);
        };

        input.addEventListener('input', applyMask);
        applyMask();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initPhoneMasks());
} else {
    initPhoneMasks();
}
