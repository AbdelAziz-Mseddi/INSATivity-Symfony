# INSATivity

A modern, responsive web platform for university students to discover upcoming and past events organized by student clubs.

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)
- [Usage](#usage)
- [Pages & Components](#pages--components)
- [Data Structure](#data-structure)
- [Contributing](#contributing)

## 🎯 Overview

**INSATivity** is a university event management platform designed for **INSAT** (Institut National des Sciences Appliquées et de la Technologie) students and clubs. It enables students to:
- Discover upcoming campus events
- View events by category (Technology, Sports, Culture, etc.)
- Manage their calendar
- Connect with student clubs
- Participate in club activities

### Target Audience
- University students (18–25)
- Student club members & organizers
- Event moderators

## ✨ Features

### For Students
- **Event Discovery**: Browse, search, and filter upcoming events
- **Advanced Search**: Filter by date, tags, club, and event type
- **Calendar Integration**: View events in a monthly calendar view
- **Event Details**: Access full event information including images, description, and organizer details
- **Club Directory**: Discover all student clubs with profiles
- **User Authentication**: Secure login and registration system

### For Club Organizers
- **Club Dashboard**: Create and manage events
- **Event Approval Workflow**: Track event status (pending, approved, rejected)
- **Media Management**: Upload event images and related media
- **Event Editing**: Update event details and information
- **Tag Management**: Categorize events with relevant tags

## 💻 Tech Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Responsive styling with custom properties (CSS variables)
- **JavaScript (ES6+)** - Interactive functionality
- **Fonts**: Google Fonts (Inter family)

### Backend
- **PHP 8+** - Server-side logic
- **JSON** - Data storage (flat-file database)

### Key Libraries & APIs
- **Fetch API** - Asynchronous HTTP requests
- **Event Listeners** - DOM interactivity

## 🗂️ Project Structure

```
Web-Dev-Project/
├── README.md                 # This file
├── pages/                    # HTML pages
│   ├── index.html           # Homepage / Events feed
│   ├── calendar.html        # Calendar view
│   ├── clubs.html           # Clubs directory
│   ├── club-dashboard.html  # Club management dashboard
│   ├── event.html           # Event details page
│   ├── login.html           # Login page
│   └── register.html        # Registration page
├── backend/                  # PHP backend logic
│   ├── events.php           # Event API endpoints
│   ├── EventManager.php     # Event management class
│   ├── clubs.php            # Clubs API endpoints
│   ├── ClubManager.php      # Club management class
│   ├── media.php            # Media handling
│   ├── MediaManager.php     # Media management class
│   ├── login.php            # Login handler
│   └── register.php         # Registration handler
├── scripts/                  # JavaScript files
│   ├── events.js            # Event page logic
│   ├── calendar.js          # Calendar functionality
│   ├── clubs.js             # Clubs page logic
│   ├── club-dashboard.js    # Dashboard logic
│   └── club-dashboard/      # Dashboard module exports
│       ├── api.js           # API communication
│       ├── constants.js     # Configuration constants
│       ├── render.js        # Rendering functions
│       └── utils.js         # Utility functions
├── styles/                   # CSS stylesheets
│   ├── base.css             # Base styles & CSS variables
│   ├── layout.css           # Layout & grid system
│   ├── components.css       # Reusable components
│   ├── homepage.css         # Homepage specific styles
│   ├── club-dashboard.css   # Dashboard specific styles
│   ├── event.css            # Event details page styles
│   └── page.css             # General page styles
├── data/                     # JSON data files
│   ├── clubs.json           # Club information & metadata
│   ├── acm_events.json      # ACM club events
│   ├── aerobotix_events.json # Aerobotix club events
│   ├── cine_radio_events.json # Ciné Radio club events
│   ├── ieee_events.json     # IEEE club events
│   ├── jci_events.json      # JCI club events
│   ├── junior_events.json   # Junior club events
│   ├── securinets_events.json # Securinets club events
│   └── theatro_events.json  # Theatro club events
└── assets/                   # Static assets
    ├── images/              # Event and club images
    │   ├── 3zero/          # 3zero club images
    │   ├── acm/            # ACM club images
    │   ├── aerobotix/      # Aerobotix club images
    │   ├── android/        # Android-related images
    │   ├── cine_radio/     # Ciné Radio images
    │   ├── genesis_labs/   # Genesis Labs images
    │   ├── ieee/           # IEEE club images
    │   ├── insat_press/    # INSAT Press images
    │   ├── jci/            # JCI club images
    │   ├── junior/         # Junior team images
    │   ├── securinets/     # Securinets club images
    │   └── theatro/        # Theatro club images
    ├── icons/              # UI icons and symbols
    └── uploads/            # User-uploaded media
```

## 🚀 Setup & Installation

### Prerequisites
- PHP 8.0 or higher
- Web server (Apache, Nginx, or built-in PHP server)
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Installation Steps

1. **Clone or download the project**
   ```bash
   cd Web-Dev-Project
   ```

2. **Ensure proper file permissions**
   ```bash
   chmod -R 755 backend/
   chmod -R 755 data/
   chmod -R 755 assets/uploads/
   ```

3. **Start a local server**
   
   Using PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   
   Or configure with Apache/Nginx

4. **Access the application**
   - Open `http://localhost:8000` in your browser
   - Navigate to the homepage

## 📖 Usage

### For Students

1. **Browsing Events**
   - Navigate to the Events page (homepage)
   - Use search and filters to find events
   - Click on an event to view full details

2. **Viewing Calendar**
   - Go to Calendar page
   - View events organized by date
   - Click on dates to see event details

3. **Discovering Clubs**
   - Visit the Clubs page
   - Browse all student clubs
   - Click on a club to learn more

4. **User Account**
   - Click the profile icon to log in
   - New users can register via the Sign Up link
   - Login to access personalized features

### For Club Organizers

1. **Access Club Dashboard**
   - Log in with club credentials
   - Navigate to Club Dashboard

2. **Create New Event**
   - Fill out the event creation form
   - Upload event images
   - Add relevant tags and category
   - Submit for approval

3. **Manage Events**
   - View all club events and their status
   - Edit pending/approved events
   - Upload media for events
   - Track event moderation status

## 🧩 Pages & Components

### 1. Homepage (pages/index.html)
- **Search bar** with filters (date, tags, club, event type)
- **Hero section** with tagline
- **Featured Events** carousel displaying approved events
- **Upcoming Events** feed with event cards
- Color-coded categories (technology, sports, culture, etc.)
- **Navigation header** with logo and menu links

### 2. Calendar Page (pages/calendar.html)
- Monthly calendar view with date navigation
- Color-coded event categories on calendar dates
- Empty state messaging for dates without events
- Event list view for selected date

### 3. Event Details Page (pages/event.html)
- Large banner image
- Full event description and metadata
- Event organizer (club) information
- Media gallery (images and videos)
- Tags and category badges
- Add-to-calendar functionality
- Share event buttons

### 4. Club Dashboard (pages/club-dashboard.html)
- Event creation and editing form
- Media upload interface
- Tag and category selection
- Event status indicator (pending, approved, rejected)
- Club member management
- Event analytics dashboard

### 5. Clubs Directory (pages/clubs.html)
- Grid layout of all student clubs
- Club cards displaying:
  - Club logo/icon
  - Club name
  - Category
  - Short bio/description
- Links to individual club profiles
- Search/filter functionality

### 6. Login Page (pages/login.html)
- Email/username input with clear labels
- Password input with show/hide toggle
- Login button and error messaging
- Link to registration page
- Helper text for form validation

### 7. Registration Page (pages/register.html)
- Student information form
- Email and password validation
- Club selection/affiliation
- Terms acceptance checkbox
- Form submission and confirmation

## 📊 Data Structure

### Clubs Data (data/clubs.json)
Stores information about all student clubs:
```json
{
  "id": "string",
  "name": "string",
  "category": "string",
  "banner": "string (path to banner image)",
  "description": "string"
}
```

### Event Data Files
Each club has a dedicated events file (e.g., `acm_events.json`) with the following structure:
```json
{
  "id": "number",
  "title": "string",
  "club": "string (club name)",
  "clubLogo": "string (path to club logo)",
  "image": "string (path to event image)",
  "date": "string (YYYY-MM-DD format)",
  "time": "string (HH:MM format)",
  "location": "string",
  "description": "string",
  "participants": "number (current attendees)",
  "maxParticipants": "number (capacity)",
  "featured": "boolean (whether event is featured on homepage)"
}
```

## 🔧 Main Components

### Backend Classes
- **EventManager**: Handles event CRUD operations
- **ClubManager**: Manages club data and relationships
- **MediaManager**: Processes and stores media files

### Frontend Modules
- **events.js**: Main event listing and filtering logic
- **calendar.js**: Calendar view and date navigation
- **clubs.js**: Clubs directory management
- **club-dashboard/api.js**: API communication from dashboard
- **club-dashboard/utils.js**: Utility functions for dashboard
- **club-dashboard/render.js**: DOM rendering functions
- **club-dashboard/constants.js**: Configuration and constants

## 🎨 Styling

The project uses a **modular CSS architecture**:
- **base.css**: CSS custom properties (variables), reset styles
- **layout.css**: Grid system and layout utilities
- **components.css**: Reusable UI components
- **Specific page stylesheets**: page-specific custom styles

### Color Scheme
- Primary: Deep Red (--deep-red)
- Accessible color-coded categories for event types

## ✅ UX Considerations
- Clear visual distinction between approved vs pending events
- Smooth transitions and hover states
- Clear empty states messaging
- Responsive design for mobile, tablet, and desktop
- Accessible color contrasts and semantic HTML

## 🤝 Contributing

When contributing to this project:

1. **Follow the existing code structure**
   - Maintain separation of concerns (HTML, CSS, JS, PHP)
   - Use meaningful variable and function names

2. **Data Guidelines**
   - Keep JSON data files in consistent format
   - Update club and event data in respective files

3. **Styling**
   - Use CSS custom properties for theming
   - Maintain responsive design principles

4. **Testing**
   - Test new features across different browsers
   - Verify responsive design on mobile devices

## 📝 License

This project is part of the INSAT university initiative.

## 📧 Contact & Support

For issues or questions regarding INSATivity, contact the development team or your club's administrator.
