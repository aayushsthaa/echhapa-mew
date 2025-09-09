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

# Recent Changes (September 2025)

## Enhanced Content Blocks System (September 9, 2025)
- **Modular Content Creation**: Completely rebuilt article creation with comprehensive content blocks system
- **Rich Text Integration**: Successfully integrated custom ProfessionalEditor into text blocks with proper content syncing
- **Advanced Image Blocks**: Enhanced image functionality with both file upload and URL options, alt text, link URLs, and alignment controls
- **Professional Video Blocks**: Added YouTube/Vimeo/custom embed support with alignment and caption options
- **Quote Blocks**: Stylized quote blocks with author attribution and enhanced visual design
- **Form Submission Fix**: Resolved textarea submission issues by implementing proper JSON serialization for content storage
- **User Experience**: Added drag-and-drop image uploads, live previews, and intuitive block management with reordering
- **CSS Styling**: Comprehensive styling for all content blocks with responsive design and hover effects

## Database & Technical Improvements
- **Upload Handler**: Enhanced image upload system with proper validation and file management
- **Content Storage**: Articles now store structured JSON content instead of plain HTML for better flexibility
- **JavaScript Architecture**: Modular JavaScript with ContentBlocksEditor class and proper rich text editor integration

## Replit Environment Setup (September 9, 2025)
- **Database Configuration**: Successfully connected PostgreSQL database using Replit's managed database service with environment variables
- **PHP Server Setup**: Configured PHP 8.2.23 development server running on port 5000 with proper host binding (0.0.0.0)
- **Deployment Configuration**: Set up autoscale deployment target for production readiness  
- **Cache Control**: Implemented no-cache headers for proper development experience in Replit's iframe environment
- **Environment Integration**: All database environment variables properly configured and working
- **Sample Data**: Successfully loaded 50 articles, 20 categories, and 3 users for immediate functionality
- **Database Tables**: All required tables created including users, articles, categories, homepage_sections, settings, and ads

## Homepage Enhancement
- **Dynamic Sections System**: Created configurable homepage sections (breaking, trending, latest, featured) with multiple layout options (banner, grid, list)
- **Database Schema Updates**: Added homepage_sections table and comments table, enhanced categories with homepage visibility controls
- **Sample Content**: Added 15+ realistic articles across 5 categories with professional images and content
- **Variable Scope Fixes**: Resolved PHP variable naming conflicts in nested loops for proper article display

## Database Improvements
- **Article Class Enhancement**: Added missing CRUD methods (getById, update, delete) for complete article management
- **Comments System**: Created Comment class structure ready for implementation
- **Category Management**: Enhanced with homepage display options and color coding

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