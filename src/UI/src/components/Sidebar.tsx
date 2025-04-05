
import {
  BarChart3,
  Users,
  Calendar,
  AreaChart,
  Home,
  Settings,
  FileText,
  TrendingUp,
  Database,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { useState } from "react";
import { useNavigate, useLocation } from "react-router-dom";

interface NavItemProps {
  icon: React.ElementType;
  title: string;
  isActive?: boolean;
  badge?: string;
  onClick: () => void;
}

const NavItem = ({ icon: Icon, title, isActive, badge, onClick }: NavItemProps) => {
  return (
    <Button
      variant="ghost"
      className={cn(
        "w-full justify-start gap-3 px-3",
        isActive
          ? "bg-sidebar-accent text-sidebar-accent-foreground"
          : "text-sidebar-foreground hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground"
      )}
      onClick={onClick}
    >
      <Icon className="h-5 w-5" />
      <span className="grow text-left">{title}</span>
      {badge && (
        <span className="bg-baseball-red text-white text-xs px-2 py-0.5 rounded-full">
          {badge}
        </span>
      )}
    </Button>
  );
};

const Sidebar = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const currentPath = location.pathname;
  
  // Set initial active item based on current path
  const getInitialActiveItem = () => {
    if (currentPath === "/") return "Dashboard";
    if (currentPath === "/team") return "Team Management";
    if (currentPath === "/players") return "Player Analysis";
    if (currentPath === "/stats") return "Game Statistics";
    if (currentPath === "/performance") return "Performance";
    if (currentPath === "/schedule") return "Schedule";
    if (currentPath === "/reports") return "Reports";
    if (currentPath === "/database") return "Database";
    if (currentPath === "/settings") return "Settings";
    return "Dashboard";
  };
  
  const [activeItem, setActiveItem] = useState(getInitialActiveItem);
  
  const navItems = [
    { title: "Dashboard", icon: Home, path: "/" },
    { title: "Team Management", icon: Users, path: "/team", badge: "New" },
    { title: "Player Analysis", icon: TrendingUp, path: "/players" },
    { title: "Game Statistics", icon: BarChart3, path: "/stats" },
    { title: "Performance", icon: AreaChart, path: "/performance" },
    { title: "Schedule", icon: Calendar, path: "/schedule" },
    { title: "Reports", icon: FileText, path: "/reports" },
    { title: "Database", icon: Database, path: "/database" },
    { title: "Settings", icon: Settings, path: "/settings" },
  ];

  const handleNavigation = (title: string, path: string) => {
    setActiveItem(title);
    navigate(path);
  };

  return (
    <div className="bg-baseball-navy text-white flex flex-col h-screen w-64 fixed left-0 top-0 pt-16 shadow-lg">
      <div className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        {navItems.map((item) => (
          <div className="mb-1" key={item.title}>
            <NavItem
              icon={item.icon}
              title={item.title}
              isActive={activeItem === item.title}
              badge={item.badge}
              onClick={() => handleNavigation(item.title, item.path)}
            />
          </div>
        ))}
      </div>
      <div className="p-4 border-t border-sidebar-border">
        <div className="flex items-center">
          <div className="w-8 h-8 rounded-full bg-baseball-red flex items-center justify-center">
            <span className="font-bold text-white">AT</span>
          </div>
          <div className="ml-3">
            <p className="text-sm font-medium">Alex Thompson</p>
            <p className="text-xs text-gray-300">Team Analyst</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;
