# Eleven Forge --- MVP Database Structure

## 1. Project Overview

**Eleven Forge** is the company's centralized **Innovation Registry and
Inventory Knowledge Base**.

The MVP is designed to:

-   Maintain a catalog of all company innovations.
-   Support **Software**, **Hardware**, and **Hybrid** innovations.
-   Record the physical inventory items/materials used to build an
    innovation.
-   Record the software technologies used to develop an innovation.
-   Maintain images and videos for each innovation.
-   Provide a public/internal showcase of selected innovations.

### Core Concept

``` text
                         INNOVATION
                             │
                  ┌──────────┴──────────┐
                  │                     │
             PHYSICAL SIDE          SOFTWARE SIDE
                  │                     │
                  ▼                     ▼
          INVENTORY ITEMS          TECHNOLOGIES
                  │
                  ▼
                 BOM
```

------------------------------------------------------------------------

## 2. MVP Tables

  \#   Table                       Purpose
  ---- --------------------------- ------------------------------------------
  1    `users`                     System users
  2    `innovation_categories`     Innovation classification
  3    `innovations`               Main innovation registry
  4    `innovation_media`          Innovation images/videos
  5    `item_categories`           Physical inventory categories
  6    `units`                     Units of measurement
  7    `inventory_items`           Physical materials/components
  8    `innovation_items`          BOM / physical items used by innovations
  9    `technologies`              Software/technology master
  10   `innovation_technologies`   Technologies used by innovations

------------------------------------------------------------------------

## 3. `users`

Stores users who manage Eleven Forge.

  Column             Purpose
  ------------------ -------------------------------------
  `id`               Primary key
  `name`             Full name
  `email`            Login/email
  `password`         Password
  `role`             Admin, Manager, Contributor, Viewer
  `status`           Active / Inactive
  `remember_token`   Laravel authentication
  `created_at`       Created date
  `updated_at`       Last update

------------------------------------------------------------------------

## 4. `innovation_categories`

Classifies innovations by business or functional area.

Examples:

-   Smart Machines
-   Software Systems
-   AI / Machine Learning
-   IoT
-   Sustainability
-   Automation
-   Internal Systems
-   Client Solutions

  Column          Purpose
  --------------- -------------------------
  `id`            Primary key
  `name`          Category name
  `slug`          URL-friendly identifier
  `description`   Category description
  `is_active`     Active/inactive
  `sort_order`    Display ordering
  `created_at`    Created date
  `updated_at`    Last update

### Relationship

``` text
INNOVATION_CATEGORY 1 ─────── N INNOVATIONS
```

------------------------------------------------------------------------

## 5. `innovations`

The main table of Eleven Forge.

  Column                   Purpose
  ------------------------ ------------------------------------------
  `id`                     Primary key
  `innovation_code`        Unique internal innovation code
  `category_id`            Innovation category
  `name`                   Innovation name
  `slug`                   URL-friendly identifier
  `type`                   Software / Hardware / Hybrid
  `tagline`                Short showcase tagline
  `short_description`      Description for cards/lists
  `description`            Full innovation description
  `problem_statement`      Problem being addressed
  `solution_description`   How the innovation addresses the problem
  `key_features`           Main features
  `benefits`               Company/client benefits
  `status`                 Lifecycle status
  `visibility`             Internal / Public
  `developed_at`           Development/completion date
  `is_featured`            Featured on showcase
  `created_by`             User who created the record
  `updated_by`             Last user who updated it
  `created_at`             Created date
  `updated_at`             Last update
  `deleted_at`             Soft delete

### Innovation Types

-   `SOFTWARE`
-   `HARDWARE`
-   `HYBRID`

### Suggested Statuses

-   `CONCEPT`
-   `DEVELOPMENT`
-   `PROTOTYPE`
-   `TESTING`
-   `COMPLETED`
-   `DEPLOYED`
-   `RETIRED`

### Visibility

-   `INTERNAL`
-   `PUBLIC`

------------------------------------------------------------------------

## 6. `innovation_media`

Stores multiple images and videos for each innovation.

  Column            Purpose
  ----------------- -------------------------------
  `id`              Primary key
  `innovation_id`   Related innovation
  `media_type`      Image / Video
  `file_path`       Storage path
  `title`           Media title
  `caption`         Media caption
  `alt_text`        Accessibility/SEO text
  `is_cover`        Main innovation image
  `is_public`       Can appear on public showcase
  `sort_order`      Gallery ordering
  `created_at`      Created date
  `updated_at`      Last update

------------------------------------------------------------------------

## 7. `item_categories`

Classifies physical inventory items.

  Column          Purpose
  --------------- --------------------------
  `id`            Primary key
  `parent_id`     Optional parent category
  `name`          Category name
  `description`   Description
  `is_active`     Active/inactive
  `sort_order`    Display ordering
  `created_at`    Created date
  `updated_at`    Last update

### Example Hierarchy

``` text
Electronics
├── Sensors
├── Controllers
├── Cameras
└── Power Supplies

Mechanical
├── Motors
├── Bearings
└── Couplings

Fabrication
├── Steel
├── Aluminum
└── Acrylic

Computing
├── Jetson
├── Raspberry Pi
└── Mini PC
```

------------------------------------------------------------------------

## 8. `units`

Standard units of measurement.

  Column         Purpose
  -------------- -----------------
  `id`           Primary key
  `name`         Unit name
  `symbol`       Short form
  `is_active`    Active/inactive
  `created_at`   Created date
  `updated_at`   Last update

Examples:

-   Piece (`pc`)
-   Set (`set`)
-   Sheet (`sheet`)
-   Meter (`m`)
-   Millimeter (`mm`)
-   Kilogram (`kg`)
-   Liter (`L`)
-   Roll (`roll`)
-   Box (`box`)

------------------------------------------------------------------------

## 9. `inventory_items`

Master catalog of all physical materials and components.

  Column               Purpose
  -------------------- ------------------------------------
  `id`                 Primary key
  `item_code`          Unique internal item code
  `category_id`        Item category
  `default_unit_id`    Default unit of measure
  `name`               Item/material name
  `item_type`          Type of physical item
  `brand`              Brand
  `model`              Model
  `manufacturer`       Manufacturer
  `part_number`        Manufacturer/internal part number
  `description`        General description
  `specification`      Technical specification
  `quantity_on_hand`   Current available quantity for MVP
  `minimum_stock`      Low-stock threshold
  `unit_cost`          Current/reference unit cost
  `status`             Active / Inactive / Discontinued
  `created_by`         User who created the item
  `created_at`         Created date
  `updated_at`         Last update
  `deleted_at`         Soft delete

### Suggested Item Types

-   `RAW_MATERIAL`
-   `COMPONENT`
-   `ELECTRONIC`
-   `MECHANICAL`
-   `MODULE`
-   `EQUIPMENT`
-   `CONSUMABLE`
-   `OTHER`

> **MVP note:** `quantity_on_hand` is acceptable for the initial release
> if Forge only needs a current inventory snapshot. If stock receiving,
> issuing, consuming, returning, and adjustment will happen inside
> Forge, add an `inventory_transactions` table before implementation.

------------------------------------------------------------------------

## 10. `innovation_items`

Bill of Materials (BOM) connecting innovations to physical inventory
items.

  Column                  Purpose
  ----------------------- ----------------------------------
  `id`                    Primary key
  `innovation_id`         Related innovation
  `inventory_item_id`     Physical inventory item
  `unit_id`               Unit used for this BOM entry
  `quantity_required`     Quantity required
  `usage_description`     How/where the item is used
  `specification_notes`   Innovation-specific requirements
  `is_optional`           Required or optional item
  `sort_order`            BOM display order
  `created_at`            Created date
  `updated_at`            Last update

### Example

``` text
SEB V4
────────────────────────────────
Jetson Nano             1 pc
High-Speed Camera       1 pc
Proximity Sensor        2 pcs
Steel Sheet             3 sheets
Bearing                 4 pcs
Power Supply            1 pc
```

------------------------------------------------------------------------

## 11. `technologies`

Master catalog of non-physical technologies.

  Column          Purpose
  --------------- -----------------------------
  `id`            Primary key
  `name`          Technology name
  `category`      Technology classification
  `description`   Description
  `website`       Reference/documentation URL
  `status`        Active / Deprecated
  `created_at`    Created date
  `updated_at`    Last update

### Suggested Technology Categories

-   Language
-   Framework
-   Database
-   AI / ML
-   Library
-   Cloud
-   Platform
-   Protocol
-   Service
-   Tool

### Examples

  Technology   Category
  ------------ -----------
  Laravel      Framework
  PHP          Language
  MySQL        Database
  Python       Language
  YOLO         AI / ML
  ONNX         AI / ML
  Unity        Platform
  Sentis       Library
  AWS          Cloud
  MQTT         Protocol

------------------------------------------------------------------------

## 12. `innovation_technologies`

Many-to-many relationship between innovations and technologies.

  Column            Purpose
  ----------------- -----------------------------
  `id`              Primary key
  `innovation_id`   Related innovation
  `technology_id`   Technology used
  `version`         Version used, if applicable
  `purpose`         How the technology is used
  `created_at`      Created date
  `updated_at`      Last update

------------------------------------------------------------------------

## 13. Complete MVP Relationship Map

``` text
                        USERS
                          │
                          │ creates
                          ▼
                ┌───────────────────┐
                │    INNOVATIONS    │
                └─────────┬─────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
        ▼                 ▼                 ▼
INNOVATION_MEDIA   INNOVATION_ITEMS   INNOVATION_TECHNOLOGIES
                          │                 │
                          ▼                 ▼
                  INVENTORY_ITEMS      TECHNOLOGIES
                          │
                    ┌─────┴─────┐
                    ▼           ▼
             ITEM_CATEGORIES   UNITS


INNOVATION_CATEGORIES
          │
          └──────────────► INNOVATIONS
```

------------------------------------------------------------------------

## 14. How Innovation Types Work

### Hardware

``` text
SEB Mini SRX
Type: HARDWARE
│
└── Physical Items / BOM
    ├── Load Cell
    ├── Steel
    ├── Sensor
    └── Controller
```

### Software

``` text
SEB Portal
Type: SOFTWARE
│
└── Technologies
    ├── Laravel
    ├── MySQL
    ├── AWS
    └── MQTT
```

### Hybrid

``` text
SEB V4
Type: HYBRID
│
├── Physical Items / BOM
│   ├── Jetson Nano
│   ├── Camera
│   ├── Sensor
│   └── Steel
│
└── Technologies
    ├── Python
    ├── YOLO
    ├── ONNX
    └── Unity
```

------------------------------------------------------------------------

## 15. MVP Application Areas

``` text
Eleven Forge
│
├── Dashboard
│
├── Innovations
│   ├── Overview
│   ├── Physical Items / BOM
│   ├── Technologies
│   └── Media
│
├── Inventory
│   ├── Items
│   ├── Categories
│   └── Units
│
├── Technologies
│
├── Showcase
│
└── Administration
    └── Users
```

------------------------------------------------------------------------

## 16. Phase 2 --- Not Part of MVP

The following can be introduced after the MVP validates actual usage:

-   Suppliers
-   Item suppliers
-   Purchasing
-   Inventory transaction history
-   Warehouses / locations
-   Innovation documents
-   Innovation team members
-   Innovation versioning
-   BOM versioning
-   Historical costing
-   Approval workflows
-   Tasks / Kanban / Gantt
-   Assembly/build records

The MVP should remain focused on:

> **What did the company create? What physical items were used to build
> it? What technologies were used to develop it? And how can the
> innovation be showcased?**
