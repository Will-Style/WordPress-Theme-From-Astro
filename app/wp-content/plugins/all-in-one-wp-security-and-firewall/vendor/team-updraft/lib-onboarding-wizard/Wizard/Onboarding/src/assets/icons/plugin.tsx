import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const plugin = ({
                                                 size = 48,
                                                 fill = 'none',
                                                 stroke = 'none',
                                                 strokeWidth = 2,
                                                 viewBox = '0 0 48 48',
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
        <path
            d="M23 38H25V34.3L32 27.3V18H16V27.3L23 34.3V38ZM19 40V36L13.15 30.15C12.7833 29.7833 12.5 29.3583 12.3 28.875C12.1 28.3917 12 27.8833 12 27.35V18C12 16.9 12.3917 15.9583 13.175 15.175C13.9583 14.3917 14.9 14 16 14H18L16 16V8C16 7.43333 16.1917 6.95833 16.575 6.575C16.9583 6.19167 17.4333 6 18 6C18.5667 6 19.0417 6.19167 19.425 6.575C19.8083 6.95833 20 7.43333 20 8V14H28V8C28 7.43333 28.1917 6.95833 28.575 6.575C28.9583 6.19167 29.4333 6 30 6C30.5667 6 31.0417 6.19167 31.425 6.575C31.8083 6.95833 32 7.43333 32 8V16L30 14H32C33.1 14 34.0417 14.3917 34.825 15.175C35.6083 15.9583 36 16.9 36 18V27.35C36 27.8833 35.9 28.3917 35.7 28.875C35.5 29.3583 35.2167 29.7833 34.85 30.15L29 36V40C29 40.5667 28.8083 41.0417 28.425 41.425C28.0417 41.8083 27.5667 42 27 42H21C20.4333 42 19.9583 41.8083 19.575 41.425C19.1917 41.0417 19 40.5667 19 40Z"
            fill={fill}
            stroke={stroke}
            strokeWidth={strokeWidth}
        />
    </svg>
);

export default plugin;