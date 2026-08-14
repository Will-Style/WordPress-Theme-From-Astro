import { memo, type ComponentType, type KeyboardEvent, type ReactNode } from 'react';
import customIcons from './customIcons';
import materialIcons from './materialIcons';

// Color mapping from our custom colors to CSS variables
const iconColors = {
  black: 'var(--teamupdraft-black)',
  green: 'var(--teamupdraft-green)',
  yellow: 'var(--teamupdraft-yellow)',
  red: 'var(--teamupdraft-red)',
  blue: 'var(--teamupdraft-blue)',
  gray: 'var(--teamupdraft-grey-400)',
  gray500: 'var(--teamupdraft-grey-500)',
  white: 'var(--teamupdraft-white)',
};

export type IconName = keyof typeof materialIcons | keyof typeof customIcons | string;
export type ColorName = keyof typeof iconColors | string;

export interface IconProps {
  name?: IconName;
  color?: ColorName;
  size?: number;
  strokeWidth?: number;
  onClick?: () => void;
  className?: string;
  stroke?: string;
  fill?: string;
  type?: 'material' | 'custom';
}

const Icon = memo((
    {
    name,
    color = 'var(--teamupdraft-orange-dark)',
    fill = 'var(--teamupdraft-orange-dark)',
    size = 18,
    stroke = 'none',
    strokeWidth = 1.5,
    onClick,
    className,
    type
    }: IconProps) => {
  // resolved color (CSS var or raw color)
  const colorVal = (iconColors[color as keyof typeof iconColors] || color) as string;

  // try to resolve components from both maps (may be undefined)
  const MaterialIcon = (materialIcons as Record<string, ComponentType<any>>)[String(name)];
  const CustomIcon = (customIcons as Record<string, ComponentType<any>>)[String(name)];

  // click handler (keyboard accessible)
  const handleClick = () => {
    if (onClick) onClick();
  };

  const handleKeyDown = (e: KeyboardEvent) => {
    if (!onClick) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onClick();
    }
  };

  // choose which icon to render based on `type` prop or availability
  let inner: ReactNode = null;

  // Check if 'name' is a URL
  const isExternalUrl = typeof name === 'string' && (name.startsWith('http://') || name.startsWith('https://'));

  if (isExternalUrl) {
    inner = <img src={name as string} alt="icon" style={{ width: size, height: size }} className="object-contain" />;
  } else if (name) { // Only try to render if a name is provided and it's not a URL
    if (type === 'custom') {
      inner = CustomIcon ? (
          <CustomIcon width={size} height={size} stroke={stroke} strokeWidth={strokeWidth} color={colorVal} fill={fill} />
      ) : MaterialIcon ? (
          <MaterialIcon sx={{ fontSize: size, color: colorVal,"& path": {
              stroke: stroke,
              strokeWidth: strokeWidth,
              fill: fill,
            }, }} />
      ) : null;
    } else { // Default to 'material' or if type is 'material'
      inner = MaterialIcon ? (
          <MaterialIcon sx={{ fontSize: size, color: colorVal,"& path": {
              stroke: stroke,
              strokeWidth: strokeWidth,
              fill: fill,
            }, }} />
      ) : CustomIcon ? (
          <CustomIcon width={size} height={size} stroke={stroke} strokeWidth={strokeWidth} color={colorVal} fill={fill} />
      ) : null;
    }
  }

  // fallback: small filled circle (keeps layout predictable) if a name was provided but no icon found
  if (!inner && name) {
    inner = <span style={{ display: 'inline-block', width: size, height: size, borderRadius: '50%', background: colorVal }} />;
  } else if (!inner && !name) { // If no name is provided, render nothing
      inner = null;
  }


  const animateCss = String(name) === 'loading-circle' ? ' animate-spin' : '';
  const finalClass = `${className ?? ''} icon-${String(name)} flex items-center justify-center${animateCss}`.trim();

  const iconElement = inner ? ( // Only render div if there's an inner element
      <div
          role={onClick ? 'button' : undefined}
          tabIndex={onClick ? 0 : undefined}
          onClick={handleClick}
          onKeyDown={handleKeyDown}
          className={finalClass}
          aria-hidden={onClick ? undefined : true}
      >
        {inner}
      </div>
  ) : null; // If no inner, render nothing

  return iconElement;
});

export default Icon;