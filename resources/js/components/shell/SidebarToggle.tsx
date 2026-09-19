import { PanelLeftClose, PanelLeftOpen } from 'lucide-react';

type Props = {
    collapsed: boolean;
    onToggle?: () => void;
};

export default function SidebarToggle({ collapsed, onToggle }: Props) {
    return (
        <button
            type="button"
            onClick={onToggle}
            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-white/10 text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
            aria-label={collapsed ? 'Expand navigation' : 'Collapse navigation'}
            aria-expanded={!collapsed}
            title={collapsed ? 'Expand navigation' : 'Collapse navigation'}
        >
            {collapsed
                ? <PanelLeftOpen size={18} aria-hidden="true" />
                : <PanelLeftClose size={18} aria-hidden="true" />}
        </button>
    );
}
