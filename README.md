# NIION CORP – Past Times

## Project Overview

Past Times is a multi-seller thrift marketplace web application developed for NIION CORP as part of the WEDE6021 Web Development Project.
The platform provides users with a modern e-commerce experience focused on buying and selling pre-owned clothing and accessories through a secure, intuitive, and responsive online marketplace.

The system enables buyers to browse and purchase thrifted items while allowing verified sellers to manage their own storefronts within the platform. Administrative moderation and seller verification ensure platform legitimacy, trust, and marketplace consistency.

---

## Problem Statement

Traditional thrift stores often lack scalable digital platforms that effectively showcase second-hand products while maintaining trust between buyers and sellers.
Past Times addresses this challenge by providing an accessible online marketplace that enhances the visibility, credibility, and convenience of thrift commerce.

---

## Project Objectives

* Develop a secure multi-vendor thrift marketplace
* Implement role-based access for Buyers, Sellers, and Administrators
* Provide seamless product browsing, purchasing, and seller communication
* Ensure mobile responsiveness and user-friendly navigation
* Simulate real-world e-commerce workflows using PHP and MySQL

---

## Key Features

### Buyer Features

* User Registration & Authentication
* Product Browsing & Advanced Filtering
* Product Search Functionality
* Shopping Cart & Checkout
* Order Tracking
* Buyer-Seller Messaging

### Seller Features

* Seller Application & Verification
* Product Listing Management
* Inventory Control
* Sales Dashboard
* Order Fulfilment Tracking

### Admin Features

* Seller Verification Management
* Product Listing Moderation
* Dispute Resolution
* User Management
* Marketplace Monitoring Dashboard

---

## Innovative Features

* Simulated Smart Item Matching / Image Search
* Escrow Payment Protection Simulation
* In-App Messaging Between Buyers & Sellers
* Social Seller Following / Community Interaction Concept
* Multi-Seller Marketplace Storefront Model

---

## UI/UX Design Prototype

The user interface and user experience were planned and prototyped in Figma prior to development to ensure consistency, responsiveness, and strong visual hierarchy across all pages.

### Figma Prototype

[View Figma Design Prototype](PASTE_FIGMA_LINK_HERE)

---

## Design Mockups

### Homepage

![Homepage Mockup](docs/design-mockups/homepage.png)

### Seller Dashboard

![Seller Dashboard](docs/design-mockups/seller-dashboard.png)

### Checkout Page

![Checkout Page](docs/design-mockups/checkout.png)

### Admin Dashboard

![Admin Dashboard](docs/design-mockups/admin-dashboard.png)

---

## Technology Stack

| Layer              | Technology              |
| ------------------ | ----------------------- |
| Frontend           | HTML5, CSS3, JavaScript |
| Backend            | PHP                     |
| Database           | MySQL                   |
| Design/Prototyping | Figma                   |
| Version Control    | Git & GitHub            |

---

## System Architecture

The application follows an MVC-inspired structured PHP architecture for maintainability and scalability.

```plaintext
/app
   /controllers
   /models
   /views
/config
/database
/public
/uploads
/routes
/docs
```

---

## Database Design

The MySQL database was designed based on the ERD and includes relational structures for:

* Users
* Seller Profiles
* Product Listings
* Orders
* Messages
* Admin Moderation
* Delivery Information

---

## Development Workflow

This repository follows structured GitHub project management practices:

* GitHub Issues for task tracking
* GitHub Projects (Kanban Board) for workflow management
* Milestones for sprint planning
* Feature Branch Workflow
* Pull Requests for controlled merges

---

## Project Board Workflow

| Column           | Description               |
| ---------------- | ------------------------- |
| Backlog          | Planned future tasks      |
| To Do            | Ready for development     |
| In Progress      | Currently being worked on |
| Testing / Review | Under verification        |
| Done             | Completed tasks           |

---

## Installation / Setup Instructions

1. Clone the Repository

```bash
git clone https://github.com/your-username/niioncorp-past-times.git
```

2. Configure Database

* Import SQL schema into MySQL
* Update database credentials in `/config/database.php`

3. Run Project

* Place project in XAMPP/WAMP `htdocs`
* Start Apache & MySQL
* Access via browser:

```plaintext
http://localhost/niioncorp-past-times
```

---

## Team Members

| Name              | Role                                     |
| ----------------- | ---------------------------------------- |
| Ivant Wambo       | Full Stack Developer / UI Implementation |
| Kgahlisho Mokoala | Backend Developer / Database Architect   |

---

## Documentation

Additional project documentation is available in the `/docs` folder:

* Research Documentation
* ERD Diagram
* Use Case Diagram
* Sitemap
* Wireframes / Mockups
* Risk Mitigation Plan

---

## Project Status

Current Development Phase: **Implementation / Testing**

---

## License

This project is developed for academic purposes under NIION CORP / WEDE6021 requirements.
