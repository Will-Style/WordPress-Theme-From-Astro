import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const minus = ({
    size = 16,
    fill = 'currentColor',
    stroke = 'none',
    strokeWidth = 1,
    viewBox = '0 0 16 16',
    ...props
}:CustomIconProps) => (
    <svg
        width={size}
        height={size}
        fill={fill}
        stroke={stroke}
        strokeWidth={strokeWidth}
        viewBox={viewBox}
        strokeLinecap="round"
        strokeLinejoin="round"
        xmlns="http://www.w3.org/2000/svg"
        {...props}
    >
        <path d="M2 7.5H14V8.5H2V7.5Z" />
    </svg>
);

export default minus;