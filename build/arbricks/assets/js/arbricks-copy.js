document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-arbricks-copy]');
    if (!el) return;

    e.preventDefault();

    // Prevent multiple clicks if already processing
    if (el.getAttribute('data-processing') === 'true') return;

    const textToCopy = el.getAttribute('data-arbricks-copy');
    if (!textToCopy) return;

    const originalHTML = el.innerHTML;
    el.setAttribute('data-processing', 'true');

    navigator.clipboard.writeText(textToCopy)
        .then(() => {
            el.innerHTML = ArBricksCopyConfig.successText;
        })
        .catch(() => {
            el.innerHTML = ArBricksCopyConfig.errorText;
        })
        .finally(() => {
            setTimeout(() => {
                el.innerHTML = originalHTML || ArBricksCopyConfig.defaultText;
                el.removeAttribute('data-processing');
            }, 1500);
        });
});