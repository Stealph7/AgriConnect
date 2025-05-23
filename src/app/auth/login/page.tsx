"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import Link from "next/link"

export default function LoginPage() {
  const [isLoading, setIsLoading] = useState(false)

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    setIsLoading(true)
    
    try {
      // TODO: Implémenter la logique de connexion
      console.log("Tentative de connexion...")
    } catch (error) {
      console.error("Erreur de connexion:", error)
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="container flex h-screen w-screen flex-col items-center justify-center">
      <Card className="w-full max-w-md p-8">
        <div className="flex flex-col space-y-2 text-center mb-8">
          <h1 className="text-2xl font-semibold tracking-tight">
            Connexion à AgriConnect
          </h1>
          <p className="text-sm text-muted-foreground">
            Entrez vos identifiants pour accéder à votre compte
          </p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
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
            <Label htmlFor="password">Mot de passe</Label>
            <Input
              id="password"
              type="password"
              required
            />
          </div>

          <Button 
            type="submit" 
            className="w-full"
            disabled={isLoading}
          >
            {isLoading ? "Connexion en cours..." : "Se connecter"}
          </Button>
        </form>

        <div className="mt-6 text-center text-sm">
          <Link 
            href="/auth/forgot-password"
            className="text-primary hover:underline"
          >
            Mot de passe oublié ?
          </Link>
        </div>

        <div className="mt-4 text-center text-sm">
          Pas encore de compte ?{" "}
          <Link 
            href="/auth/register"
            className="text-primary hover:underline"
          >
            S'inscrire
          </Link>
        </div>
      </Card>
    </div>
  )
}
