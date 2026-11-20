const editor = document.querySelector('#jsEditor');
const runButton = document.querySelector('#runCodeBtn');
const output = document.querySelector('#outputConsole');

runButton.addEventListener('click', () => {
    runButton.disabled = true;
    output.textContent = 'Выполняем код…';
    const frame = document.createElement('iframe');
    frame.hidden = true;
    frame.title = 'Среда выполнения JavaScript';
    frame.setAttribute('sandbox', 'allow-scripts');

    function finish(message) {
        clearTimeout(timeout);
        window.removeEventListener('message', receiveResult);
        frame.remove();
        output.textContent = message;
        runButton.disabled = false;
    }

    function receiveResult(event) {
        if (event.source === frame.contentWindow && event.origin === 'null' && typeof event.data === 'string') {
            finish(event.data);
        }
    }

    const timeout = setTimeout(() => finish('Не удалось завершить выполнение. Попробуйте снова.'), 5000);
    window.addEventListener('message', receiveResult);
    const code = editor.value;
    frame.addEventListener('load', () => frame.contentWindow.postMessage(code, '*'), { once: true });
    frame.src = 'js/playground-frame.html';
    document.body.append(frame);
});
