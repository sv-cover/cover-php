function isIOS() {
    let userAgent = navigator.userAgent || navigator.vendor || window.opera;
    // IE on windows phone has included iPhone at some point.
    return /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
}

/**
 * copyTextToClipboard function courtecy of Dean Taylor on stackoverflow
 * https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript 
 */
export function copyTextToClipboard(text) {
    let textArea = document.createElement("textarea");

    // Make sure textarea is invisible
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.style.fontSize = '16px';
    textArea.value = text;
    document.body.appendChild(textArea);

    // Select and copy contents
    if (isIOS()) {
        let range = document.createRange();
        range.selectNodeContents(textArea);
        let selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        textArea.setSelectionRange(0, 999999);
    } else {
        textArea.select();
    }


    try {
        document.execCommand('copy');
    } catch (err) {
        return false;
    }

    // Clean up
    document.body.removeChild(textArea);

    return true;
}
