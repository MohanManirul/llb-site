# React + Inertia Frontend Folder Structure

> **This is the target, not a description of the repo — read the tree as illustrative,
> not as a file list.** Almost none of it matches today: of the 108 `components/ui`
> entries below only six exist, `lib/utils.ts` and the whole `config/` hook-and-context
> set are absent, and `pages/` has different features. Follow the shape and the rules
> for **new** feature folders; follow the surrounding code when editing an existing one,
> and check what is actually on disk before importing anything from here.
> `CLAUDE.md` holds the target-vs-current table.

```bash
resources
├── css
│   └── app.css
│
├── views
│   └── app.blade.php
│
└── js
    ├── app.tsx
    │
    ├── types
    │   ├── inertia.d.ts
    │   └── vite-env.d.ts
    │
    ├── lib
    │   ├── api-client.ts
    │   └── utils.ts
    │
    ├── config
    │   ├── ThemeContext.tsx
    │   ├── sidebarNav.ts
    │   ├── useMediaQuery.ts
    │   └── useModalA11y.ts
    │
    ├── components
    │   ├── admin
    │   │   └── AdminPageLayout.tsx
    │   │
    │   ├── common
    │   │   ├── DashboardLayout.tsx
    │   │   ├── HeroBodyBackdrop.tsx
    │   │   ├── PageTransition.tsx
    │   │   ├── SiteHeader.tsx
    │   │   └── theme-toggle.tsx
    │   │
    │   └── ui
    │       ├── Accordion.tsx
    │       ├── AccordionCard.tsx
    │       ├── AccordionFilled.tsx
    │       ├── AccordionNumbered.tsx
    │       ├── ActionCard.tsx
    │       ├── Alert.tsx
    │       ├── AlertCard.tsx
    │       ├── AlertDialog.tsx
    │       ├── AreaChartRechart.tsx
    │       ├── Avatar.tsx
    │       ├── Badge.tsx
    │       ├── BarChartRechart.tsx
    │       ├── BarSpinner.tsx
    │       ├── BorderedListTable.tsx
    │       ├── BorderedTable.tsx
    │       ├── BounceSpinner.tsx
    │       ├── Breadcrumb.tsx
    │       ├── Button.tsx
    │       ├── ButtonGroup.tsx
    │       ├── Calendar.tsx
    │       ├── Card.tsx
    │       ├── CardCarousel.tsx
    │       ├── CardParts.tsx
    │       ├── CardTimeline.tsx
    │       ├── Carousel.tsx
    │       ├── Checkbox.tsx
    │       ├── ClassicTimeline.tsx
    │       ├── ClockSpinner.tsx
    │       ├── Combobox.tsx
    │       ├── ContentCarousel.tsx
    │       ├── ContextMenu.tsx
    │       ├── DashboardHeader.tsx
    │       ├── Dialog.tsx
    │       ├── Divider.tsx
    │       ├── DotSpinner.tsx
    │       ├── DoughnutChartRechart.tsx
    │       ├── Drawer.tsx
    │       ├── DropdownMenu.tsx
    │       ├── DuelRingSpinner.tsx
    │       ├── EmptyState.tsx
    │       ├── FeatureCard.tsx
    │       ├── FileUpload.tsx
    │       ├── FormField.tsx
    │       ├── FullWidthHeroCarousel.tsx
    │       ├── HorizontalTimeline.tsx
    │       ├── IconBox.tsx
    │       ├── IconButton.tsx
    │       ├── ImageCarousel.tsx
    │       ├── index.ts
    │       ├── InfoCard.tsx
    │       ├── Input.tsx
    │       ├── Label.tsx
    │       ├── LineChartRechart.tsx
    │       ├── Link.tsx
    │       ├── ListCard.tsx
    │       ├── LogoCarousel.tsx
    │       ├── Modal.tsx
    │       ├── OrbitSpinner.tsx
    │       ├── Pagination.tsx
    │       ├── PieChartRechart.tsx
    │       ├── Popover.tsx
    │       ├── PosCartItem.tsx
    │       ├── PosProductCard.tsx
    │       ├── PosProductList.tsx
    │       ├── PosTopNavbar.tsx
    │       ├── ProductCard.tsx
    │       ├── ProductCarousel.tsx
    │       ├── ProgressBar.tsx
    │       ├── ProgressCard.tsx
    │       ├── ProgressRing.tsx
    │       ├── PullToRefreshIndicator.tsx
    │       ├── Radio.tsx
    │       ├── RadioGroup.tsx
    │       ├── RichTextEditor.tsx
    │       ├── RingFadeSpinner.tsx
    │       ├── RingSpinner.tsx
    │       ├── RippleSpinner.tsx
    │       ├── ScrollArea.tsx
    │       ├── SearchableDropdown.tsx
    │       ├── SearchInput.tsx
    │       ├── Section.tsx
    │       ├── Select.tsx
    │       ├── Separator.tsx
    │       ├── Sheet.tsx
    │       ├── Sidebar.tsx
    │       ├── sidebarIcons.tsx
    │       ├── Slider.tsx
    │       ├── sound
    │       │   ├── error.mp3
    │       │   └── success.mp3
    │       ├── SquareFlipSpinner.tsx
    │       ├── StatsCard.tsx
    │       ├── Stepper.tsx
    │       ├── styles.ts
    │       ├── Switch.tsx
    │       ├── Table.tsx
    │       ├── TableCard.tsx
    │       ├── Tabs.tsx
    │       ├── Tag.tsx
    │       ├── TestimonialCarousel.tsx
    │       ├── Textarea.tsx
    │       ├── Toast.tsx
    │       ├── ToggleButton.tsx
    │       ├── Tooltip.tsx
    │       ├── Typography.tsx
    │       ├── UserCard.tsx
    │       ├── VirtualizedDataTable.tsx
    │       └── VirtualizedListView.tsx
    │
    └── pages
        └── admin
            ├── event-participants
            │   └── page.tsx
            │
            ├── home
            │   └── page.tsx
            │
            └── login
                ├── api.ts
                ├── page.tsx
                └── types.ts
```

# Architecture Flow

```text
Laravel Route (Inertia::render)
  ↓
app.blade.php
  ↓
app.tsx (Inertia bootstrap)
  ↓
pages/{feature}/page.tsx
  ↓
pages/{feature}/api.ts
  ↓
lib/api-client.ts
  ↓
Laravel API endpoint
```

# Rules

* Feature-wise folder structure under `pages/`
* `page.tsx` handles UI, local state, and Inertia navigation only
* API calls stay inside `api.ts`
* Request and response types stay inside `types.ts`
* Form validation stays inside `validation.ts` when needed
* Reusable UI primitives stay inside `components/ui`
* Shared layout shells stay inside `components/common` and `components/admin`
* App-wide config, context, and hooks stay inside `../../config`
* Axios client and shared utilities stay inside `lib/`
* Global Inertia page props stay inside `types/`
* Import UI components from `@/components/ui` barrel (`index.ts`)
* Use `cn()` from `lib/utils` for className merging — **target only.** There is no
  `cn()`, no `lib/utils.ts`, and neither `clsx` nor `tailwind-merge` is installed.
  Compose class strings directly until someone adds it deliberately
* Keep code clean, minimal, and scalable
* Avoid unnecessary methods and files
* No comments inside code
