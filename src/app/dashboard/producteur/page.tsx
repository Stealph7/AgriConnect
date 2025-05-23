"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function ProducteurDashboard() {
  const [isLoading, setIsLoading] = useState(false)

  return (
    <div className="container py-8">
      <h1 className="text-3xl font-bold mb-8">Tableau de bord Producteur</h1>

      <Tabs defaultValue="profile" className="space-y-8">
        <TabsList>
          <TabsTrigger value="profile">Mon Profil</TabsTrigger>
          <TabsTrigger value="annonces">Mes Annonces</TabsTrigger>
          <TabsTrigger value="messages">Messagerie</TabsTrigger>
          <TabsTrigger value="alertes">Alertes SMS</TabsTrigger>
          <TabsTrigger value="stats">Statistiques</TabsTrigger>
        </TabsList>

        {/* Profil */}
        <TabsContent value="profile">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Mon Profil</h2>
            <form className="space-y-6">
              <div className="grid grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="name">Nom complet</Label>
                  <Input id="name" defaultValue="John Doe" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input id="email" type="email" defaultValue="john@example.com" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="phone">Téléphone</Label>
                  <Input id="phone" defaultValue="+225 XX XX XX XX" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="region">Région</Label>
                  <Select defaultValue="abidjan">
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionnez votre région" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="abidjan">Abidjan</SelectItem>
                      <SelectItem value="yamoussoukro">Yamoussoukro</SelectItem>
                      <SelectItem value="bouake">Bouaké</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <Button>Mettre à jour le profil</Button>
            </form>
          </Card>
        </TabsContent>

        {/* Annonces */}
        <TabsContent value="annonces">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Nouvelle Annonce</h2>
            <form className="space-y-6">
              <div className="grid grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="produit">Produit</Label>
                  <Input id="produit" placeholder="Ex: Cacao, Café..." />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="quantite">Quantité (kg)</Label>
                  <Input id="quantite" type="number" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="prix">Prix par kg (FCFA)</Label>
                  <Input id="prix" type="number" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="saison">Saison</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionnez la saison" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="principale">Saison Principale</SelectItem>
                      <SelectItem value="intermediaire">Saison Intermédiaire</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea id="description" placeholder="Décrivez votre produit..." />
              </div>
              <Button>Publier l'annonce</Button>
            </form>

            <div className="mt-8">
              <h3 className="text-xl font-semibold mb-4">Mes annonces en ligne</h3>
              <div className="grid gap-4">
                {/* Liste des annonces */}
                <Card className="p-4">
                  <div className="flex justify-between items-start">
                    <div>
                      <h4 className="font-semibold">Cacao Grade A</h4>
                      <p className="text-sm text-gray-600">1000 kg - 1500 FCFA/kg</p>
                      <p className="text-sm text-gray-600">Saison Principale</p>
                    </div>
                    <div className="space-x-2">
                      <Button variant="outline" size="sm">Modifier</Button>
                      <Button variant="destructive" size="sm">Supprimer</Button>
                    </div>
                  </div>
                </Card>
              </div>
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
                  <Input placeholder="Rechercher une conversation..." />
                </div>
                <div className="space-y-2 p-4">
                  {/* Liste des contacts */}
                  <div className="p-3 bg-gray-100 rounded cursor-pointer">
                    <h4 className="font-semibold">Coopérative ABC</h4>
                    <p className="text-sm text-gray-600">Dernier message...</p>
                  </div>
                </div>
              </div>

              {/* Zone de chat */}
              <div className="col-span-2 border rounded-lg flex flex-col">
                <div className="p-4 border-b">
                  <h3 className="font-semibold">Coopérative ABC</h3>
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

        {/* Alertes SMS */}
        <TabsContent value="alertes">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Alertes SMS</h2>
            <div className="space-y-6">
              <div className="grid gap-4">
                <Card className="p-4">
                  <h4 className="font-semibold">Alerte Météo</h4>
                  <p className="text-sm text-gray-600">Fortes pluies prévues dans votre région</p>
                  <p className="text-sm text-gray-400">Reçu le: 12/03/2024</p>
                </Card>
                <Card className="p-4">
                  <h4 className="font-semibold">Conseil Agricole</h4>
                  <p className="text-sm text-gray-600">Période optimale pour la récolte du cacao</p>
                  <p className="text-sm text-gray-400">Reçu le: 10/03/2024</p>
                </Card>
              </div>
            </div>
          </Card>
        </TabsContent>

        {/* Statistiques */}
        <TabsContent value="stats">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Statistiques</h2>
            <div className="grid grid-cols-3 gap-6">
              <Card className="p-4">
                <h4 className="font-semibold">Vues totales</h4>
                <p className="text-3xl font-bold mt-2">1,234</p>
              </Card>
              <Card className="p-4">
                <h4 className="font-semibold">Messages reçus</h4>
                <p className="text-3xl font-bold mt-2">56</p>
              </Card>
              <Card className="p-4">
                <h4 className="font-semibold">Ventes réalisées</h4>
                <p className="text-3xl font-bold mt-2">12</p>
              </Card>
            </div>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
