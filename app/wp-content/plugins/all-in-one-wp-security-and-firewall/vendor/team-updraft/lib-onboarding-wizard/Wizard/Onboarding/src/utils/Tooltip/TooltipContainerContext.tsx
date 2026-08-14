// TooltipContainerContext.tsx
import { createContext, useContext, type ReactNode } from 'react';

const TooltipContainerContext = createContext<HTMLElement | null>(null);
export const useTooltipContainer = () => useContext(TooltipContainerContext);

export const TooltipContainerProvider = ({ container, children }: { container: HTMLElement | null; children: ReactNode }) => (
    <TooltipContainerContext.Provider value={container}>{children}</TooltipContainerContext.Provider>
);


