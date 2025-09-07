# Overview

This is a professional news portal built with PHP, featuring a complete content management system (CMS) with both frontend public interface and backend admin dashboard. The application provides article publishing, user management, and a rich text editing experience for content creators.

# User Preferences

Preferred communication style: Simple, everyday language.

# System Architecture

## Frontend Architecture
- **Multi-layered CSS Design**: Uses CSS custom properties (CSS variables) for consistent theming across admin and public interfaces
- **Component-based Styling**: Separate stylesheets for admin dashboard (`admin.css`), rich text editor (`editor.css`), and public frontend (`style.css`)
- **Progressive Enhancement**: JavaScript features like lazy loading, smooth scrolling, and debounced search enhance the basic functionality
- **Custom Rich Text Editor**: Built-in WYSIWYG editor (`ProfessionalEditor` class) with formatting tools and media insertion capabilities

## Backend Architecture
- **PHP-based Server**: Traditional server-side rendering with PHP handling all backend logic
- **Session Management**: PHP sessions for user authentication and state management
- **MVC-like Structure**: Organized separation between presentation (CSS/JS), business logic (PHP), and data handling

## Content Management Features
- **Rich Text Editing**: Custom editor with formatting options, heading styles, text alignment, and media insertion
- **SEO-friendly URLs**: Dynamic slug generation from article titles for better search engine optimization
- **Responsive Design**: Mobile-first approach with flexible grid system and responsive navigation
- **Image Optimization**: Lazy loading implementation for improved page performance

## User Interface Design
- **Professional Theming**: Gradient color schemes with CSS custom properties for easy customization
- **Admin Dashboard**: Fixed sidebar navigation with collapsible mobile view
- **Interactive Elements**: Hover effects, transitions, and active states for better user experience
- **Accessibility Features**: Proper semantic HTML structure and keyboard navigation support

# External Dependencies

## Frontend Libraries
- **Font Awesome**: Icon library for UI elements and editor toolbar buttons
- **Inter Font**: Google Fonts integration for typography consistency
- **Modern Browser APIs**: Intersection Observer for lazy loading, CSS Grid and Flexbox for layout

## Development Tools
- **Native JavaScript**: No external JavaScript frameworks, using vanilla JS for all interactions
- **CSS Grid & Flexbox**: Modern CSS layout techniques for responsive design
- **PHP Session Handling**: Built-in PHP session management for user authentication

## Browser Compatibility
- **Modern Browser Support**: Utilizes contemporary web standards like CSS custom properties, Intersection Observer, and ES6+ JavaScript features
- **Progressive Enhancement**: Core functionality works without JavaScript, enhanced features layer on top