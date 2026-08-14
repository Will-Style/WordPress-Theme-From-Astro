interface CustomIconProps extends React.SVGProps<SVGSVGElement> {
    size?: number;
    fill?: string;
    stroke?: string;
    strokeWidth?: number;
    viewBox?: string;
}

const right = ({
                                                  size = 24,
                                                  fill = 'none',
                                                  stroke = 'none',
                                                  strokeWidth = 2,
                                                  viewBox = '0 0 24 24',
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
        <path d="M9 18l6-6-6-6" />
    </svg>
);

export default right;