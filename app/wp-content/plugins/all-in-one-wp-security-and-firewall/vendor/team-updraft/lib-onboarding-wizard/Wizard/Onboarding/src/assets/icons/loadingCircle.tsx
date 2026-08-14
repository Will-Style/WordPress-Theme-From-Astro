import type { SVGProps } from 'react';

interface CustomIconProps extends SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const loadingCircle = ({
                                                size = 18,
                                                fill = 'none',
                                                stroke = 'none',
                                                strokeWidth = 2,
                                                viewBox = '0 0 18 18',
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
            d="M9 16.5C7.975 16.5 7.00625 16.3031 6.09375 15.9094C5.18125 15.5156 4.38438 14.9781 3.70312 14.2969C3.02188 13.6156 2.48438 12.8188 2.09063 11.9062C1.69688 10.9938 1.5 10.025 1.5 9C1.5 7.9625 1.69688 6.99063 2.09063 6.08438C2.48438 5.17813 3.02188 4.38438 3.70312 3.70312C4.38438 3.02188 5.18125 2.48438 6.09375 2.09063C7.00625 1.69688 7.975 1.5 9 1.5C9.2125 1.5 9.39063 1.57188 9.53438 1.71563C9.67813 1.85938 9.75 2.0375 9.75 2.25C9.75 2.4625 9.67813 2.64062 9.53438 2.78438C9.39063 2.92813 9.2125 3 9 3C7.3375 3 5.92188 3.58438 4.75313 4.75313C3.58438 5.92188 3 7.3375 3 9C3 10.6625 3.58438 12.0781 4.75313 13.2469C5.92188 14.4156 7.3375 15 9 15C10.6625 15 12.0781 14.4156 13.2469 13.2469C14.4156 12.0781 15 10.6625 15 9C15 8.7875 15.0719 8.60938 15.2156 8.46563C15.3594 8.32188 15.5375 8.25 15.75 8.25C15.9625 8.25 16.1406 8.32188 16.2844 8.46563C16.4281 8.60938 16.5 8.7875 16.5 9C16.5 10.025 16.3031 10.9938 15.9094 11.9062C15.5156 12.8188 14.9781 13.6156 14.2969 14.2969C13.6156 14.9781 12.8219 15.5156 11.9156 15.9094C11.0094 16.3031 10.0375 16.5 9 16.5Z"
            fill={fill}
            stroke={stroke}
            strokeWidth={strokeWidth}
        />
    </svg>
);

export default loadingCircle;