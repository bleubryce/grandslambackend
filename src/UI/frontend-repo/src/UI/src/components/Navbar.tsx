
import { useState } from "react";
import { Bell, Menu, Search, User, LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useNavigate } from "react-router-dom";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/contexts/AuthContext";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const Navbar = () => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const navigate = useNavigate();
  const { toast } = useToast();
  const { logout } = useAuth();

  const handleLogout = async () => {
    try {
      await logout();
      toast({
        title: "Logged out successfully",
        description: "You have been logged out of your account",
      });
      navigate("/login");
    } catch (error) {
      console.error("Logout failed:", error);
      toast({
        title: "Logout failed",
        description: "An error occurred during logout",
        variant: "destructive",
      });
    }
  };

  const handleMenuToggle = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
    console.log("Mobile menu toggled:", !isMobileMenuOpen);
  };

  const handleNotificationClick = () => {
    toast({
      title: "Notifications",
      description: "You have no new notifications",
    });
  };

  const handleProfileAction = (action: string) => {
    console.log(`Profile action: ${action}`);
    if (action === 'profile') {
      toast({
        title: "Profile",
        description: "Profile feature coming soon",
      });
    } else if (action === 'settings') {
      toast({
        title: "Settings",
        description: "Settings feature coming soon",
      });
      navigate("/settings");
    } else if (action === 'logout') {
      handleLogout();
    }
  };

  return (
    <nav className="bg-white border-b border-gray-200 px-4 py-2.5 fixed left-0 right-0 top-0 z-50">
      <div className="flex flex-wrap justify-between items-center">
        <div className="flex justify-start items-center">
          <Button
            variant="ghost"
            size="icon"
            className="mr-2 md:hidden"
            onClick={handleMenuToggle}
          >
            <Menu className="h-6 w-6" />
          </Button>
          <a href="/" className="flex items-center" onClick={(e) => {
            e.preventDefault();
            navigate('/');
          }}>
            <div className="bg-baseball-navy rounded-full w-10 h-10 flex items-center justify-center mr-2">
              <span className="text-white font-bold text-xl">BA</span>
            </div>
            <span className="self-center text-xl font-semibold whitespace-nowrap hidden sm:block">
              Baseball Analytics
            </span>
          </a>
        </div>

        <div className="flex items-center lg:order-2">
          <div className="hidden sm:flex mr-3 relative">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <Search className="w-4 h-4 text-gray-400" />
            </div>
            <input
              type="text"
              className="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2"
              placeholder="Search..."
              onChange={(e) => console.log("Search:", e.target.value)}
            />
          </div>

          <Button variant="ghost" size="icon" className="mr-2" onClick={handleNotificationClick}>
            <Bell className="h-5 w-5" />
          </Button>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="rounded-full">
                <User className="h-5 w-5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>My Account</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handleProfileAction('profile')}>
                Profile
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleProfileAction('settings')}>
                Settings
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleProfileAction('logout')}>
                <LogOut className="h-4 w-4 mr-2" />
                Sign out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
