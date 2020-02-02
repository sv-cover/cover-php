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
    textArea.value = text;
    document.body.appendChild(textArea);

    // Select and copy contents
    textArea.select();

    try {
        let successful = document.execCommand('copy');
    } catch (err) {
        return false;
    }

    // Clean up
    document.body.removeChild(textArea);

    return true;
}
