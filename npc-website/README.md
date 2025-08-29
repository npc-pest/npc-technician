# NPC Website - Professional Home Facility Services

A modern React website for NPC (Professional Home Facility Services) that replicates the functionality of the Flutter mobile app with a clean, professional UI similar to Urban Company.

## Features

### 🏠 Landing Page
- Modern hero section with call-to-action
- Service showcase with professional design
- Features highlighting company benefits
- Responsive design for all devices

### 🔐 Authentication
- User registration and login
- Role-based authentication (User, Admin, Technician)
- Session management with localStorage
- Form validation and error handling

### 👤 User Dashboard
- Browse available services
- View special offers and discounts
- Search functionality
- Clean, card-based layout
- Navigation sidebar

### 🛠️ Admin Dashboard
- Administrative panel (under development)
- Service and pricing management
- Booking management
- Technician management
- Analytics and reports

### 🔧 Technician Dashboard
- Order management (under development)
- Status updates
- Work schedule
- Service history

### 📱 Responsive Design
- Mobile-first approach
- Tablet and desktop optimized
- Modern UI/UX with smooth animations
- Professional color scheme

## Technology Stack

- **Frontend**: React 18 with TypeScript
- **Styling**: Tailwind CSS
- **State Management**: React Context API
- **HTTP Client**: Axios
- **Routing**: React Router DOM
- **Animations**: Framer Motion
- **Icons**: React Icons
- **Notifications**: React Hot Toast
- **Data Fetching**: React Query

## Project Structure

```
npc-website/
├── public/
│   ├── index.html
│   └── manifest.json
├── src/
│   ├── components/
│   │   └── LoadingSpinner.tsx
│   ├── config/
│   │   └── api.ts
│   ├── context/
│   │   └── AuthContext.tsx
│   ├── pages/
│   │   ├── LandingPage.tsx
│   │   ├── LoginPage.tsx
│   │   ├── SignupPage.tsx
│   │   ├── UserDashboard.tsx
│   │   ├── AdminDashboard.tsx
│   │   ├── TechnicianDashboard.tsx
│   │   ├── ServiceDetails.tsx
│   │   ├── BookingHistory.tsx
│   │   ├── ContactPage.tsx
│   │   └── ProfilePage.tsx
│   ├── services/
│   │   └── api.ts
│   ├── types/
│   │   └── index.ts
│   ├── App.tsx
│   ├── index.tsx
│   └── index.css
├── package.json
└── README.md
```

## API Integration

The website uses the same API endpoints as the Flutter app:

- **Base URL**: `https://npcpest.com/npc/`
- **Authentication**: Session-based with Session-ID header
- **Endpoints**: User login, admin login, technician login, services, bookings, etc.

## Getting Started

### Prerequisites

- Node.js (v14 or higher)
- npm or yarn

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd npc-website
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm start
```

4. Open [http://localhost:3000](http://localhost:3000) to view it in the browser.

### Available Scripts

- `npm start` - Runs the app in development mode
- `npm run build` - Builds the app for production
- `npm test` - Launches the test runner
- `npm run eject` - Ejects from Create React App

## Design System

### Colors
- **Primary**: Teal (#0f766e)
- **Secondary**: Blue (#1e40af)
- **Background**: Gray (#f8fafc)
- **Text**: Gray (#1e293b)

### Typography
- **Font Family**: Inter (Google Fonts)
- **Weights**: 300, 400, 500, 600, 700

### Components
- Modern card designs
- Smooth hover effects
- Consistent spacing
- Professional shadows
- Responsive grid layouts

## Features in Development

- Service booking functionality
- Payment integration
- Real-time notifications
- Advanced admin features
- Technician order management
- Booking history and management
- Contact form and support
- Profile management

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is proprietary and confidential.

## Support

For support and questions, please contact the development team. 