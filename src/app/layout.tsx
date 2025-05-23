import type { Metadata } from "next"
import { Inter } from "next/font/google"
import { Header } from "@/components/Header"
import { Footer } from "@/components/Footer"
import "./globals.css"

const inter = Inter({ subsets: ["latin"] })

export const metadata: Metadata = {
  title: "AgriConnect - Modernisation de l'agriculture en Côte d'Ivoire",
  description: "Plateforme de connexion entre agriculteurs, acheteurs et coopératives",
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="fr">
      <body className={inter.className}>
        <Header />
        <main className="min-h-screen pt-16">
          {children}
        </main>
        <Footer />
      </body>
    </html>
  )
}
