import { memo } from '@wordpress/element';
import type { MouseEvent } from 'react';
import FieldWrapper from './FieldWrapper';
import ButtonInput from '../Inputs/ButtonInput';
import Icon from '../../utils/Icon';
import useOnboardingStore from '../../store/useOnboardingStore';
import useAlertStore from '../../store/useAlertStore';

interface StatusSection {
    title: string;
    content: string;
    content_html?: string;
}

interface StatusAction {
    type: 'button' | 'link';
    label: string;
    onClick?: string;
    groupId?: string;
    href?: string;
    target?: string;
    rel?: string;
}

interface StatusFieldConfig {
    id: string;
    label?: string;
    context?: any;
    context_html?: string;
    container_class?: string;
    title_class?: string;
    content_class?: string;
    actions_class?: string;
    sections?: StatusSection[];
    actions?: StatusAction[];
    [key: string]: any;
}

interface StatusProps {
    field: StatusFieldConfig;
    value?: any;
    onChange?: (value: any) => void;
}

const Status = ({ field }: StatusProps) => {
    const { settings, setValue, getValue } = useOnboardingStore();
    const { setAlertState, getAlertState } = useAlertStore();
    
    const {
        container_class = 'max-w-sm bg-gray-100 rounded-2xl p-6 text-gray-800',
        title_class = 'text-lg font-semibold mb-1',
        content_class = 'text-base mb-2',
        actions_class = 'flex items-center gap-4 !text-[#C4511C] font-semibold',
        sections = [],
        actions = [],
    } = field;
    
    /**
     * Resolve placeholders [[field_id]] using Zustand getValue.
     */
    const resolvePlaceholders = (text: string): string => {
        if (!text || typeof text !== 'string') {
            return text;
        }
        return text.replace(/\[\[([a-zA-Z0-9_\-]+)\]\]/g, (_match, fieldId) => String(getValue(fieldId)));
    };

    const renderSection = (section: StatusSection, index: number) => {
        const resolvedContent = section.content_html 
            ? resolvePlaceholders(section.content_html)
            : resolvePlaceholders(section.content);
            
        return (
            <div key={index}>
                <h3 className={title_class}>
                    {section.title}
                </h3>
                {section.content_html ? (
                    <p 
                        className={content_class}
                        dangerouslySetInnerHTML={{ __html: resolvedContent }}
                    />
                ) : (
                    <p className={content_class}>
                        {resolvedContent}
                    </p>
                )}
            </div>
        );
    };

    const renderAction = (action: StatusAction, index: number) => {
        // Common button props
        const buttonProps = {
            key: index,
            btnVariant: 'transparent' as const,
            size: 'sm' as const,
            rawClassName: 'flex items-center gap-1 hover:underline',
        };

        if (action.type === 'button') {
            const handleClick = (e: MouseEvent<HTMLButtonElement>) => {
                const actionName = action.onClick || '';
                const groupId = action.groupId || field.id;
                const actions = (window as any).pluginOnboardingActions || {};
                const fn = actions[actionName];
                
                if (typeof fn === 'function') {
                    try {
                        fn(field, settings, setAlertState, setValue);
                    } catch (e) {
                        console.error('Error in pluginOnboardingActions.' + actionName, e);
                    }
                } else {
                    setValue(`${groupId}_completed`, false);
                }
            };
            
            return (
                <ButtonInput
                    {...buttonProps}
                    onClick={handleClick}
                >
                    {action.label}
                </ButtonInput>
            );
        }

        if (action.type === 'link') {
            return (
                <ButtonInput
                    {...buttonProps}
                    link={action.href || '#'}
                >
                    {action.label}
                </ButtonInput>
            );
        }

        return null;
    };

    const alertGroupId = field.alert_group_id || field.id;
    const alertState = getAlertState(alertGroupId);
    const isUpdating = alertState.isUpdating;

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label || ''}
            context={field.context}
            contextHtml={field.context_html ?? ''}
        >
            <div className="relative w-full">
                <div
                    className={isUpdating ? 'pointer-events-none opacity-70' : ''}
                    aria-disabled={isUpdating}
                >
                    <div className={container_class}>
                        {sections.map((section, index) => renderSection(section, index))}
                        
                        {actions.length > 0 && (
                            <>
                                <h3 className={title_class}>
                                    {field.actions_title || 'Manage'}
                                </h3>
                                <div className={actions_class}>
                                    {actions.map((action, index) => renderAction(action, index))}
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {isUpdating && (
                    <div className="absolute inset-0 flex items-center justify-center bg-white/60 cursor-not-allowed">
                        <Icon
                            name="loading-circle"
                            size={20}
                            color="var(--teamupdraft-orange-dark)"
                            fill="var(--teamupdraft-orange-dark)"
                        />
                    </div>
                )}
            </div>
        </FieldWrapper>
    );
};

export default memo(Status);
