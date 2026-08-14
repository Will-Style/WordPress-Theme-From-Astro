export const normalizeValue = (value: any): any => {
    if (typeof value === 'boolean' || typeof value === 'number') {
        return value;
    }
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed === '1') return true;
        if (trimmed === '0') return false;
        if (trimmed.toLowerCase() === 'true') return true;
        if (trimmed.toLowerCase() === 'false') return false;
        return trimmed;
    }
    return value;
};