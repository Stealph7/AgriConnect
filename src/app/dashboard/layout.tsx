"use client"

import { useState } from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  const pathname = usePathname()
  const [isSidebarOpen, setIsSidebarOpen] = useState(true)

  const isActive = (path: string) => pathname?.startsWith(path)

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Sidebar */}
      <aside className={`fixed left-0 top-0 z-40 h-screen w-64 bg-white border-r transition-transform ${
        isSidebarOpen ? "translate-x-0" : "-translate-x-full"
      }`}>
        <div className="flex h-16 items-center justify-between border-b px-4">
          <Link href="/" className="text-xl font-bold">
            AgriConnect
          </Link>
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setIsSidebarOpen(false)}
          >
            ✕
          </Button>
        </div>

        <nav className="space-y-1 p-4">
          <Link 
            href="/dashboard/producteur"
            className={`flex items-center px-4 py-2 rounded-lg ${
              isActive("/dashboard/producteur")
                ? "bg-primary text-primary-foreground"
                : "hover:bg-gray-100"
            }`}
          >
            Interface Producteur
          </Link>
          <Link 
            href="/dashboard/acheteur"
            className={`flex items-center px-4 py-2 rounded-lg ${
              isActive("/dashboard/acheteur")
                ? "bg-primary text-primary-foreground"
                : "hover:bg-gray-100"
            }`}
          >
            Interface Acheteur
          </Link>
          <Link 
            href="/dashboard/admin"
            className={`flex items-center px-4 py-2 rounded-lg ${
              isActive("/dashboard/admin")
                ? "bg-primary text-primary-foreground"
                : "hover:bg-gray-100"
            }`}
          >
            Interface Admin
          </Link>
        </nav>
      </aside>

      {/* Main Content */}
      <div className={`${isSidebarOpen ? "ml-64" : "ml-0"} transition-margin`}>
        {/* Top Bar */}
        <header className="h-16 bg-white border-b px-4 flex items-center justify-between">
          {!isSidebarOpen && (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsSidebarOpen(true)}
            >
              ☰
            </Button>
          )}
          <div className="flex items-center space-x-4 ml-auto">
            <Button variant="ghost" size="sm">
              Notifications
            </Button>
            <Button variant="ghost" size="sm">
              Mon Profil
            </Button>
            <Button variant="outline" size="sm">
              Déconnexion
            </Button>
          </div>
        </header>

        {/* Page Content */}
        <main className="p-4">
          {children}
        </main>
      </div>
    </div>
  )
}
