import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const bolt = ({
    size = 60,
    fill = 'none',
    stroke = 'none',
    strokeWidth = 1,
    viewBox = '0 0 29 41',
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
            d="M11.8704 32.9832L22.2204 20.5832H14.2204L15.6704 9.23318L6.42045 22.5832H13.3704L11.8704 32.9832ZM8.77045 26.5832H2.57045C1.77045 26.5832 1.17878 26.2248 0.795447 25.5082C0.412113 24.7915 0.45378 24.0998 0.920447 23.4332L15.8704 1.93318C16.2038 1.46652 16.6371 1.14152 17.1704 0.958182C17.7038 0.774849 18.2538 0.783182 18.8204 0.983182C19.3871 1.18318 19.8038 1.53318 20.0704 2.03318C20.3371 2.53318 20.4371 3.06652 20.3704 3.63318L18.7704 16.5832H26.5204C27.3871 16.5832 27.9954 16.9665 28.3454 17.7332C28.6954 18.4998 28.5871 19.2165 28.0204 19.8832L11.5704 39.5832C11.2038 40.0165 10.7538 40.2999 10.2204 40.4332C9.68711 40.5665 9.17045 40.5165 8.67045 40.2832C8.17045 40.0499 7.77878 39.6915 7.49545 39.2082C7.21211 38.7248 7.10378 38.1998 7.17045 37.6332L8.77045 26.5832Z"
        />
    </svg>
);

export default bolt;