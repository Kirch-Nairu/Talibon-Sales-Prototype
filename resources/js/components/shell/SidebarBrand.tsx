import MunicipalBrand from '../MunicipalBrand';

type Props = {
    compact: boolean;
    mobile: boolean;
};

export default function SidebarBrand({ compact }: Props) {
    return (
        <div className={`border-b border-white/10 ${compact ? 'px-2 py-4' : 'px-5 py-5'}`}>
            <div className={compact ? 'flex justify-center' : undefined}>
                <MunicipalBrand inverse compact iconOnly={compact} />
            </div>
            {!compact && (
                <div className="mt-3 text-xs font-medium text-blue-200">
                    Municipal workspace
                </div>
            )}
        </div>
    );
}
