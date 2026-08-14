/**
 * Does not work on localhost
 * @param value
 * @constructor
 */
const Copy = (value) => {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(value)
            .then(() => console.log("Key copied to clipboard"))
            .catch((err) => console.error("Clipboard write failed", err));
    } else {
        const input = document.createElement("input");
        input.value = value;
        document.body.appendChild(input);
        input.focus();
        input.select();

        try {
            const success = document.execCommand("copy");
            console.log(success ? "Copied!" : "Copy failed");
        } catch (err) {
            console.error("Fallback copy error:", err);
        }

        document.body.removeChild(input);
    }
};
export default Copy;