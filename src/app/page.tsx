"use client"

import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import Link from "next/link"

export default function Home() {
  return (
    <main className="min-h-screen bg-white">
      {/* Hero Section */}
      <section className="relative h-[80vh] bg-black/60 flex items-center justify-center text-white">
        <div className="absolute inset-0 z-0">
          <div 
            className="w-full h-full bg-[url('https://images.pexels.com/photos/2389022/pexels-photo-2389022.jpeg')] bg-cover bg-center"
            style={{ filter: 'brightness(0.6)' }}
          />
        </div>
        <div className="relative z-10 text-center space-y-6 max-w-4xl mx-auto px-4">
          <h1 className="text-5xl font-bold">AgriConnect</h1>
          <p className="text-xl">Modernisation de l'agriculture en Côte d'Ivoire</p>
          <div className="flex gap-4 justify-center">
            <Button asChild>
              <Link href="/auth/login">Connexion</Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href="/auth/register">Inscription</Link>
            </Button>
          </div>
        </div>
      </section>

      {/* Services Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Nos Services</h2>
          <div className="grid md:grid-cols-3 gap-8">
            <Card className="p-6">
              <h3 className="text-xl font-semibold mb-4">SMS Agricoles</h3>
              <p className="text-gray-600">Recevez des alertes météo, conseils agricoles et informations sur les maladies directement sur votre téléphone.</p>
            </Card>
            <Card className="p-6">
              <h3 className="text-xl font-semibold mb-4">Application Mobile</h3>
              <p className="text-gray-600">Gérez vos annonces, communiquez avec les acheteurs et suivez vos ventes depuis votre smartphone.</p>
            </Card>
            <Card className="p-6">
              <h3 className="text-xl font-semibold mb-4">Données Drones</h3>
              <p className="text-gray-600">Surveillez vos cultures et optimisez vos rendements grâce à nos services de drones agricoles.</p>
            </Card>
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Témoignages</h2>
          <div className="grid md:grid-cols-2 gap-8">
            <Card className="p-6">
              <p className="text-gray-600 mb-4">"AgriConnect a révolutionné ma façon de vendre mes produits. Je reçois maintenant des alertes SMS qui m'aident à mieux gérer mes cultures."</p>
              <p className="font-semibold">- Kouamé A., Producteur de cacao</p>
            </Card>
            <Card className="p-6">
              <p className="text-gray-600 mb-4">"Grâce à la plateforme, nous pouvons facilement trouver des producteurs locaux et négocier directement avec eux."</p>
              <p className="font-semibold">- Marie K., Coopérative agricole</p>
            </Card>
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-3xl mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Contactez-nous</h2>
          <Card className="p-8">
            <form className="space-y-6">
              <div>
                <label className="block text-sm font-medium mb-2">Nom complet</label>
                <input type="text" className="w-full p-2 border rounded" required />
              </div>
              <div>
                <label className="block text-sm font-medium mb-2">Email</label>
                <input type="email" className="w-full p-2 border rounded" required />
              </div>
              <div>
                <label className="block text-sm font-medium mb-2">Message</label>
                <textarea className="w-full p-2 border rounded" rows={4} required />
              </div>
              <Button type="submit" className="w-full">Envoyer</Button>
            </form>
          </Card>
        </div>
      </section>
    </main>
  )
}
