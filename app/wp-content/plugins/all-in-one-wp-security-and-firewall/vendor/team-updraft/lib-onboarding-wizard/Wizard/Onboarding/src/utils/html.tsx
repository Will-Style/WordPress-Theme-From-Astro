import * as DOMPurify from "isomorphic-dompurify";

export const isLikelyHtml = (s: string) => /<[^>]+>/.test(s);

const sanitizeHtml = (dirty: string, custom?: (dirty: string) => string) => {
    if (custom) {
        return custom(dirty);
    }
    return DOMPurify.sanitize(dirty, {
        ADD_ATTR: ['target', 'rel', 'class', 'style'],
    });
};

const HtmlBlock = ({ html }: { html: string }) => (
    <span dangerouslySetInnerHTML={{ __html: html }} />
);

export const renderPossiblyHtml = (value: string, sanitize?: (dirty: string) => string) => {
    if (isLikelyHtml(value)) {
        const clean = sanitizeHtml(value, sanitize);
        return <HtmlBlock html={clean} />;
    }
    return <>{value}</>;
};