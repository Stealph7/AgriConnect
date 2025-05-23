"use client"

import Link from "next/link"
import { Button } from "@/components/ui/button"
import { NavigationMenu, NavigationMenuItem, NavigationMenuLink, NavigationMenuList } from "@/components/ui/navigation-menu"

export function Header() {
  return (
    <header className="fixed w-full top-0 z-50 bg-white border-b">
      <div className="max-w-7xl mx-auto px-4">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <Link href="/" className="text-xl font-bold">
            AgriConnect
          </Link>

          {/* Navigation principale */}
          <NavigationMenu>
            <NavigationMenuList className="hidden md:flex gap-6">
              <NavigationMenuItem>
                <Link href="/services" className="text-gray-600 hover:text-gray-900">
                  Services
                </Link>
              </NavigationMenuItem>
              <NavigationMenuItem>
                <Link href="/about" className="text-gray-600 hover:text-gray-900">
                  À propos
                </Link>
              </NavigationMenuItem>
              <NavigationMenuItem>
                <Link href="/contact" className="text-gray-600 hover:text-gray-900">
                  Contact
                </Link>
              </NavigationMenuItem>
            </NavigationMenuList>
          </NavigationMenu>

          {/* Boutons d'authentification */}
          <div className="flex items-center gap-4">
            <Button variant="outline" asChild>
              <Link href="/auth/login">
                Connexion
              </Link>
            </Button>
            <Button asChild>
              <Link href="/auth/register">
                Inscription
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </header>
  )
}
