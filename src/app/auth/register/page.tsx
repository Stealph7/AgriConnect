"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import Link from "next/link"

export default function RegisterPage() {
  const [isLoading, setIsLoading] = useState(false)
  const [userType, setUserType] = useState("producteur")

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setIsLoading(true)
    
    try {
      // TODO: Implémenter la logique d'inscription
      console.log("Tentative d'inscription...")
    } catch (error) {
      console.error("Erreur d'inscription:", error)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="container flex h-screen w-screen flex-col items-center justify-center">
      <Card className="w-full max-w-md p-8">
        <div className="flex flex-col space-y-2 text-center mb-8">
          <h1 className="text-2xl font-semibold tracking-tight">
            Créer un compte AgriConnect
          </h1>
          <p className="text-sm text-muted-foreground">
            Rejoignez notre plateforme agricole innovante
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-2">
            <Label htmlFor="userType">Type de compte</Label>
            <Select
              value={userType}
              onValueChange={setUserType}
            >
              <SelectTrigger>
                <SelectValue placeholder="Sélectionnez votre type de compte" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="producteur">Producteur</SelectItem>
                <SelectItem value="acheteur">Acheteur</SelectItem>
                <SelectItem value="cooperative">Coopérative</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="name">Nom complet</Label>
            <Input
              id="name"
              type="text"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="exemple@agriconnect.ci"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="phone">Numéro de téléphone</Label>
            <Input
              id="phone"
              type="tel"
              placeholder="+225 XX XX XX XX"
              required
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="password">Mot de passe</Label>
            <Input
              id="password"
              type="password"
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="confirmPassword">Confirmer le mot de passe</Label>
            <Input
              id="confirmPassword"
              type="password"
              required
            />
          </div>

          {userType === "producteur" && (
            <div className="space-y-2">
              <Label htmlFor="region">Région</Label>
              <Select>
                <SelectTrigger>
                  <SelectValue placeholder="Sélectionnez votre région" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="abidjan">Abidjan</SelectItem>
                  <SelectItem value="yamoussoukro">Yamoussoukro</SelectItem>
                  <SelectItem value="bouake">Bouaké</SelectItem>
                  <SelectItem value="daloa">Daloa</SelectItem>
                  <SelectItem value="korhogo">Korhogo</SelectItem>
                </SelectContent>
              </Select>
            </div>
          )}

          <Button 
            type="submit" 
            className="w-full"
            disabled={isLoading}
          >
            {isLoading ? "Inscription en cours..." : "S'inscrire"}
          </Button>
        </form>

        <div className="mt-6 text-center text-sm">
          Déjà inscrit ?{" "}
          <Link 
            href="/auth/login"
            className="text-primary hover:underline"
          >
            Se connecter
          </Link>
        </div>
      </Card>
    </div>
  )
}
