import { Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AppearanceTabs from '@/components/appearance-tabs';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as incidentsIndex } from '@/routes/incidents';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Incidencias',
        href: incidentsIndex(),
        icon: ClipboardList,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={incidentsIndex()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <AppearanceTabs className="group-data-[collapsible=icon]:hidden" />
            </SidebarFooter>
        </Sidebar>
    );
}
