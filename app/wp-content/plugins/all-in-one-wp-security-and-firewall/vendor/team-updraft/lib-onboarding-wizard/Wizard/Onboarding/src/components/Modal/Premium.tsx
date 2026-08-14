import { memo, useMemo } from "react";
import Icon from "../../utils/Icon";
import { renderPossiblyHtml } from '../../utils/html';

const Premium = memo(({ bullets }: { bullets?: string[] | string[][] }) => {    
    if (!bullets || bullets.length === 0) return null;

    /**
     * Normalize bullets
     */
    const rows = useMemo(() => {
        const isRowArray = Array.isArray(bullets[0]);
        return isRowArray ? bullets : bullets.map((b) => [b]);
    }, [bullets]);

    return (
        <div className="table mx-auto">
            {rows.map((row, rowIndex) => (
                <div key={`row-${rowIndex}`} className="table-row">
                    {row.map((bullet, colIndex) => {
                        const baseText =
                        typeof bullet === "string" ? bullet : bullet?.text;

                        const baseIcon =
                        typeof bullet === "string"
                            ? "check"
                            : bullet?.icon ?? "check";

                        return (
                            <div
                                key={`bullet-${rowIndex}-${colIndex}`}
                                className="table-cell pr-3"
                            >
                                <div className="flex items-start gap-2">
                                    <Icon
                                        name={baseIcon}
                                        color="var(--teamupdraft-orange-dark)"
                                        size={25}
                                    />
                                    <div className="text-[var(--teamupdraft-grey-700)] text-md font-normal leading-relaxed">
                                        {renderPossiblyHtml(baseText)}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            ))}
        </div>
    );
});

export default Premium;