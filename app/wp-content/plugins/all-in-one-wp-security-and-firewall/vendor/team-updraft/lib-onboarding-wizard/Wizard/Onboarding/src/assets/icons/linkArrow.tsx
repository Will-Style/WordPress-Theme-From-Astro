import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const linkArrow = ({
    size = 16,
    fill = 'none',
    stroke = 'none',
    strokeWidth = 1,
    viewBox = '0 0 10 9',
    ...props
} :CustomIconProps) => (
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
            d="M7.85835 2.57477L1.92502 8.50811C1.80279 8.63033 1.64724 8.69144 1.45835 8.69144C1.26946 8.69144 1.11391 8.63033 0.991683 8.50811C0.869461 8.38588 0.80835 8.23033 0.80835 8.04144C0.80835 7.85255 0.869461 7.69699 0.991683 7.57477L6.92502 1.64144H1.85835C1.66946 1.64144 1.51113 1.57755 1.38335 1.44977C1.25557 1.32199 1.19168 1.16366 1.19168 0.974772C1.19168 0.785883 1.25557 0.62755 1.38335 0.499772C1.51113 0.371994 1.66946 0.308105 1.85835 0.308105H8.52502C8.7139 0.308105 8.87224 0.371994 9.00002 0.499772C9.12779 0.62755 9.19168 0.785883 9.19168 0.974772V7.64144C9.19168 7.83033 9.12779 7.98866 9.00002 8.11644C8.87224 8.24422 8.7139 8.3081 8.52502 8.3081C8.33613 8.3081 8.17779 8.24422 8.05002 8.11644C7.92224 7.98866 7.85835 7.83033 7.85835 7.64144V2.57477Z"
        />
    </svg>
);

export default linkArrow;