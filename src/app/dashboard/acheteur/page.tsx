"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function AcheteurDashboard() {
  const [isLoading, setIsLoading] = useState(false)

  return (
    <div className="container py-8">
      <h1 className="text-3xl font-bold mb-8">Tableau de bord Acheteur/Coopérative</h1>

      <Tabs defaultValue="recherche" className="space-y-8">
        <TabsList>
          <TabsTrigger value="recherche">Recherche Produits</TabsTrigger>
          <TabsTrigger value="offres">Offres Disponibles</TabsTrigger>
          <TabsTrigger value="messages">Messagerie</TabsTrigger>
          <TabsTrigger value="commandes">Commandes</TabsTrigger>
        </TabsList>

        {/* Recherche de Produits */}
        <TabsContent value="recherche">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Recherche de Produits Agricoles</h2>
            <form className="space-y-6">
              <div className="grid grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="region">Région</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Toutes les régions" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="abidjan">Abidjan</SelectItem>
                      <SelectItem value="yamoussoukro">Yamoussoukro</SelectItem>
                      <SelectItem value="bouake">Bouaké</SelectItem>
                      <SelectItem value="daloa">Daloa</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="produit">Type de Produit</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Tous les produits" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="cacao">Cacao</SelectItem>
                      <SelectItem value="cafe">Café</SelectItem>
                      <SelectItem value="anacarde">Anacarde</SelectItem>
                      <SelectItem value="coton">Coton</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="saison">Saison</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Toutes les saisons" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="principale">Saison Principale</SelectItem>
                      <SelectItem value="intermediaire">Saison Intermédiaire</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="prix">Prix Maximum (FCFA/kg)</Label>
                  <Input type="number" id="prix" />
                </div>
              </div>
              <Button>Rechercher</Button>
            </form>
          </Card>
        </TabsContent>

        {/* Offres Disponibles */}
        <TabsContent value="offres">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Offres Disponibles</h2>
            <div className="grid grid-cols-3 gap-6">
              {/* Exemple d'offre */}
              <Card className="p-4">
                <div className="aspect-video bg-gray-100 rounded-lg mb-4"></div>
                <h3 className="font-semibold">Cacao Grade A</h3>
                <p className="text-sm text-gray-600">1000 kg disponibles</p>
                <p className="text-sm text-gray-600">1500 FCFA/kg</p>
                <p className="text-sm text-gray-600">Région: Abidjan</p>
                <div className="mt-4 space-x-2">
                  <Button size="sm">Contacter</Button>
                  <Button size="sm" variant="outline">Détails</Button>
                </div>
              </Card>
            </div>
          </Card>
        </TabsContent>

        {/* Messagerie */}
        <TabsContent value="messages">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Messagerie</h2>
            <div className="grid grid-cols-3 gap-6 h-[600px]">
              {/* Liste des conversations */}
              <div className="border rounded-lg overflow-hidden">
                <div className="p-4 border-b">
                  <Input placeholder="Rechercher un producteur..." />
                </div>
                <div className="space-y-2 p-4">
                  <div className="p-3 bg-gray-100 rounded cursor-pointer">
                    <h4 className="font-semibold">Producteur XYZ</h4>
                    <p className="text-sm text-gray-600">Dernier message...</p>
                  </div>
                </div>
              </div>

              {/* Zone de chat */}
              <div className="col-span-2 border rounded-lg flex flex-col">
                <div className="p-4 border-b">
                  <h3 className="font-semibold">Producteur XYZ</h3>
                </div>
                <div className="flex-1 p-4">
                  {/* Messages */}
                </div>
                <div className="p-4 border-t">
                  <form className="flex gap-2">
                    <Input placeholder="Votre message..." className="flex-1" />
                    <Button>Envoyer</Button>
                  </form>
                </div>
              </div>
            </div>
          </Card>
        </TabsContent>

        {/* Commandes */}
        <TabsContent value="commandes">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Historique des Commandes</h2>
            <div className="space-y-4">
              <Card className="p-4">
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-semibold">Commande #123</h4>
                    <p className="text-sm text-gray-600">500 kg de Cacao Grade A</p>
                    <p className="text-sm text-gray-600">Total: 750,000 FCFA</p>
                    <p className="text-sm text-gray-600">Date: 12/03/2024</p>
                  </div>
                  <div>
                    <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                      Livré
                    </span>
                  </div>
                </div>
              </Card>
            </div>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
