import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const continueArrowRight = ({
    size = 16,
    fill = 'none',
    stroke = 'none',
    strokeWidth = 1,
    viewBox = '0 0 12 9',
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
            d="M9.1125 5.24989H0.75C0.5375 5.24989 0.359375 5.17801 0.215625 5.03426C0.071875 4.89051 0 4.71239 0 4.49989C0 4.28739 0.071875 4.10926 0.215625 3.96551C0.359375 3.82176 0.5375 3.74989 0.75 3.74989H9.1125L6.975 1.61239C6.825 1.46239 6.75312 1.28739 6.75937 1.08739C6.76562 0.887387 6.8375 0.712387 6.975 0.562387C7.125 0.412387 7.30312 0.334262 7.50937 0.328012C7.71562 0.321762 7.89375 0.393637 8.04375 0.543637L11.475 3.97489C11.55 4.04989 11.6031 4.13114 11.6344 4.21864C11.6656 4.30614 11.6812 4.39989 11.6812 4.49989C11.6812 4.59989 11.6656 4.69364 11.6344 4.78114C11.6031 4.86864 11.55 4.94989 11.475 5.02489L8.04375 8.45614C7.89375 8.60614 7.71562 8.67801 7.50937 8.67176C7.30312 8.66551 7.125 8.58739 6.975 8.43739C6.8375 8.28739 6.76562 8.11239 6.75937 7.91239C6.75312 7.71239 6.825 7.53739 6.975 7.38739L9.1125 5.24989Z"
        />
    </svg>
);

export default continueArrowRight;