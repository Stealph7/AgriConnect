export function Footer() {
  return (
    <footer className="bg-gray-900 text-white">
      <div className="max-w-7xl mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          {/* À propos */}
          <div>
            <h3 className="text-lg font-semibold mb-4">AgriConnect</h3>
            <p className="text-gray-400">
              Modernisation de l'agriculture en Côte d'Ivoire à travers des solutions numériques innovantes.
            </p>
          </div>

          {/* Services */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Services</h3>
            <ul className="space-y-2 text-gray-400">
              <li>SMS Agricoles</li>
              <li>Application Mobile</li>
              <li>Services Drone</li>
              <li>Conseil Agricole</li>
            </ul>
          </div>

          {/* Liens utiles */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Liens utiles</h3>
            <ul className="space-y-2 text-gray-400">
              <li>À propos</li>
              <li>Contact</li>
              <li>Conditions d'utilisation</li>
              <li>Politique de confidentialité</li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="text-lg font-semibold mb-4">Contact</h3>
            <ul className="space-y-2 text-gray-400">
              <li>Email: contact@agriconnect.ci</li>
              <li>Tél: +225 XX XX XX XX</li>
              <li>Abidjan, Côte d'Ivoire</li>
            </ul>
          </div>
        </div>

        {/* Copyright */}
        <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; {new Date().getFullYear()} AgriConnect. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  )
}
