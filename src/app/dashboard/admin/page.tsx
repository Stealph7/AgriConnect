"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

export default function AdminDashboard() {
  const [isLoading, setIsLoading] = useState(false)

  return (
    <div className="container py-8">
      <h1 className="text-3xl font-bold mb-8">Tableau de bord Administrateur</h1>

      <Tabs defaultValue="users" className="space-y-8">
        <TabsList>
          <TabsTrigger value="users">Utilisateurs</TabsTrigger>
          <TabsTrigger value="annonces">Annonces</TabsTrigger>
          <TabsTrigger value="stats">Statistiques</TabsTrigger>
          <TabsTrigger value="content">Contenus</TabsTrigger>
          <TabsTrigger value="security">Sécurité</TabsTrigger>
        </TabsList>

        {/* Gestion des Utilisateurs */}
        <TabsContent value="users">
          <Card className="p-6">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-semibold">Gestion des Utilisateurs</h2>
              <Input className="w-64" placeholder="Rechercher un utilisateur..." />
            </div>

            <div className="space-y-4">
              {/* Exemple d'utilisateur */}
              <Card className="p-4">
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-semibold">John Doe</h4>
                    <p className="text-sm text-gray-600">john@example.com</p>
                    <p className="text-sm text-gray-600">Producteur - Abidjan</p>
                  </div>
                  <div className="space-x-2">
                    <Button variant="outline" size="sm">Modifier</Button>
                    <Button variant="destructive" size="sm">Suspendre</Button>
                  </div>
                </div>
              </Card>
            </div>
          </Card>
        </TabsContent>

        {/* Modération des Annonces */}
        <TabsContent value="annonces">
          <Card className="p-6">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-semibold">Modération des Annonces</h2>
              <Select defaultValue="all">
                <SelectTrigger className="w-64">
                  <SelectValue placeholder="Filtrer par statut" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Toutes les annonces</SelectItem>
                  <SelectItem value="pending">En attente</SelectItem>
                  <SelectItem value="approved">Approuvées</SelectItem>
                  <SelectItem value="rejected">Rejetées</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-4">
              {/* Exemple d'annonce */}
              <Card className="p-4">
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-semibold">Cacao Grade A - 1000kg</h4>
                    <p className="text-sm text-gray-600">Par: John Doe</p>
                    <p className="text-sm text-gray-600">Prix: 1500 FCFA/kg</p>
                  </div>
                  <div className="space-x-2">
                    <Button variant="outline" size="sm">Voir détails</Button>
                    <Button variant="default" size="sm">Approuver</Button>
                    <Button variant="destructive" size="sm">Rejeter</Button>
                  </div>
                </div>
              </Card>
            </div>
          </Card>
        </TabsContent>

        {/* Statistiques */}
        <TabsContent value="stats">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Statistiques Globales</h2>
            <div className="grid grid-cols-4 gap-6 mb-8">
              <Card className="p-4">
                <h4 className="font-semibold">Utilisateurs Total</h4>
                <p className="text-3xl font-bold mt-2">1,234</p>
              </Card>
              <Card className="p-4">
                <h4 className="font-semibold">Annonces Actives</h4>
                <p className="text-3xl font-bold mt-2">567</p>
              </Card>
              <Card className="p-4">
                <h4 className="font-semibold">Transactions</h4>
                <p className="text-3xl font-bold mt-2">89</p>
              </Card>
              <Card className="p-4">
                <h4 className="font-semibold">Volume (FCFA)</h4>
                <p className="text-3xl font-bold mt-2">12.5M</p>
              </Card>
            </div>

            {/* Graphiques et tendances seraient ajoutés ici */}
            <div className="grid grid-cols-2 gap-6">
              <Card className="p-4 h-64">
                <h4 className="font-semibold mb-4">Évolution des inscriptions</h4>
                {/* Graphique */}
              </Card>
              <Card className="p-4 h-64">
                <h4 className="font-semibold mb-4">Volume des transactions</h4>
                {/* Graphique */}
              </Card>
            </div>
          </Card>
        </TabsContent>

        {/* Gestion des Contenus */}
        <TabsContent value="content">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Gestion des Contenus</h2>
            
            {/* Alertes SMS */}
            <div className="mb-8">
              <h3 className="text-xl font-semibold mb-4">Nouvelle Alerte SMS</h3>
              <form className="space-y-4">
                <div className="space-y-2">
                  <Label>Type d'alerte</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner le type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="meteo">Météo</SelectItem>
                      <SelectItem value="maladie">Maladie</SelectItem>
                      <SelectItem value="conseil">Conseil Agricole</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Message</Label>
                  <Textarea placeholder="Contenu de l'alerte..." />
                </div>
                <div className="space-y-2">
                  <Label>Région ciblée</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner la région" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Toutes les régions</SelectItem>
                      <SelectItem value="abidjan">Abidjan</SelectItem>
                      <SelectItem value="yamoussoukro">Yamoussoukro</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button>Envoyer l'alerte</Button>
              </form>
            </div>

            {/* Actualités */}
            <div>
              <h3 className="text-xl font-semibold mb-4">Actualités</h3>
              <form className="space-y-4">
                <div className="space-y-2">
                  <Label>Titre</Label>
                  <Input placeholder="Titre de l'actualité" />
                </div>
                <div className="space-y-2">
                  <Label>Contenu</Label>
                  <Textarea placeholder="Contenu de l'actualité..." rows={6} />
                </div>
                <Button>Publier</Button>
              </form>
            </div>
          </Card>
        </TabsContent>

        {/* Sécurité */}
        <TabsContent value="security">
          <Card className="p-6">
            <h2 className="text-2xl font-semibold mb-6">Paramètres de Sécurité</h2>
            
            <div className="space-y-6">
              {/* Gestion des rôles */}
              <div>
                <h3 className="text-xl font-semibold mb-4">Gestion des Rôles</h3>
                <Card className="p-4">
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <div>
                        <h4 className="font-semibold">Administrateur</h4>
                        <p className="text-sm text-gray-600">Accès complet à la plateforme</p>
                      </div>
                      <Button variant="outline" size="sm">Gérer les permissions</Button>
                    </div>
                    <div className="flex justify-between items-center">
                      <div>
                        <h4 className="font-semibold">Modérateur</h4>
                        <p className="text-sm text-gray-600">Modération des contenus</p>
                      </div>
                      <Button variant="outline" size="sm">Gérer les permissions</Button>
                    </div>
                  </div>
                </Card>
              </div>

              {/* Journaux d'activité */}
              <div>
                <h3 className="text-xl font-semibold mb-4">Journaux d'Activité</h3>
                <Card className="p-4">
                  <div className="space-y-2">
                    <div className="flex justify-between items-center text-sm">
                      <p>Connexion admin - IP: 192.168.1.1</p>
                      <p className="text-gray-600">Il y a 2 heures</p>
                    </div>
                    <div className="flex justify-between items-center text-sm">
                      <p>Modification des permissions utilisateur</p>
                      <p className="text-gray-600">Il y a 3 heures</p>
                    </div>
                  </div>
                </Card>
              </div>
            </div>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}
