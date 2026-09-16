import MunicipalBrand from '../MunicipalBrand';

type Props = {
    compact: boolean;
    mobile: boolean;
};

export default function SidebarBrand({ compact }: Props) {
    return (
        <div className={`border-b border-white/10 ${compact ? 'px-2 py-3' : 'px-4 py-3.5'}`}>
            <div className={compact ? 'flex justify-center' : undefined}>
                <MunicipalBrand inverse compact iconOnly={compact} />
            </div>
        </div>
    );
}
