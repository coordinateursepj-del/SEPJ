# Route & Link Audit — SEPJ Gabès

## PUBLIC WEBSITE — Navigation Audit

### Public Navbar (`public/includes/nav.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | nav_home | `index.php` | `public/index.php` | ✅ Yes | ✅ Yes | None |
| 2 | nav_about | `page.php?slug=about-company` | `public/page.php` | ✅ Yes | ✅ If slug exists in DB | None |
| 3 | nav_director | `page.php?slug=director-message` | `public/page.php` | ✅ Yes | ✅ If slug exists in DB | None |
| 4 | nav_projects | `projects.php` | `public/projects.php` | ✅ Yes | ✅ Yes | None |
| 5 | nav_services | `services.php` | `public/services.php` | ✅ Yes | ✅ Yes | None |
| 6 | nav_rse | `rse.php` | `public/rse.php` | ✅ Yes | ✅ Yes | None |
| 7 | nav_resources | `resources.php` | `public/resources.php` | ✅ Yes | ✅ Yes | None |
| 8 | nav_sports | `sports.php` | `public/sports.php` | ✅ Yes | ✅ Yes | None |
| 9 | nav_news | `post.php?type=post` | `public/post.php` | ❌ **MISSING** | ❌ **Broken** | Create `public/post.php` |
| 10 | nav_activities | `activities.php` | `public/activities.php` | ✅ Yes | ✅ Yes | None |
| 11 | nav_prizes | `prizes.php` | `public/prizes.php` | ✅ Yes | ✅ Yes | None |
| 12 | nav_gallery | `gallery.php` | `public/gallery.php` | ✅ Yes | ✅ Yes | None |
| 13 | nav_videos | `videos.php` | `public/videos.php` | ✅ Yes | ✅ Yes | None |
| 14 | nav_contact | `contact.php` | `public/contact.php` | ✅ Yes | ✅ Yes | None |
| 15 | Search icon | `search.php` | `public/search.php` | ✅ Yes | ✅ Yes | None |

### Public Footer (`public/includes/footer.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | nav_projects | `projects.php` | `public/projects.php` | ✅ Yes | ✅ Yes | None |
| 2 | nav_services | `services.php` | `public/services.php` | ✅ Yes | ✅ Yes | None |
| 3 | nav_news | `post.php?type=post` | `public/post.php` | ❌ **MISSING** | ❌ **Broken** | Create `public/post.php` |
| 4 | nav_contact | `contact.php` | `public/contact.php` | ✅ Yes | ✅ Yes | None |

### Public Homepage CTAs (`public/index.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | nav_news (hero) | `post.php?type=post` | `public/post.php` | ❌ **MISSING** | ❌ **Broken** | Create `public/post.php` |
| 2 | nav_projects (hero) | `projects.php` | `public/projects.php` | ✅ Yes | ✅ Yes | None |
| 3 | nav_contact (hero) | `contact.php` | `public/contact.php` | ✅ Yes | ✅ Yes | None |
| 4 | View all news | `post.php?type=post` | `public/post.php` | ❌ **MISSING** | ❌ **Broken** | Create `public/post.php` |
| 5 | View all projects | `projects.php` | `public/projects.php` | ✅ Yes | ✅ Yes | None |
| 6 | View all activities | `activities.php` | `public/activities.php` | ✅ Yes | ✅ Yes | None |
| 7 | View all gallery | `gallery.php` | `public/gallery.php` | ✅ Yes | ✅ Yes | None |

### Public Content Cards (index.php, projects.php, etc.)

| # | Area | Current href | Expected file | Exists? | Works? | Fix |
|---|------|--------------|---------------|---------|--------|-----|
| 1 | Post cards (index) | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 2 | Project cards (index) | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 3 | Activity cards (index) | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 4 | Project listing cards | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 5 | Service listing cards | (no link) | N/A | N/A | N/A | None |
| 6 | RSE listing cards | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 7 | Resource listing cards | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |
| 8 | Sport listing cards | `page.php?slug={slug}` | `public/page.php` | ✅ Yes | ✅ If slug in DB | None |

---

## ADMIN WEBSITE — Navigation Audit

### Admin Sidebar (`admin/includes/sidebar.php`)

**Critical problem:** ALL sidebar links are relative. They work from `/admin/dashboard.php` but BREAK from subdirectory pages like `/admin/content/`, `/admin/messages/`, `/admin/media/`, `/admin/settings/`, `/admin/users/`.

| # | Label | Current href | From `/admin/dashboard.php` | From `/admin/content/` | From `/admin/messages/` | Fix needed |
|---|-------|--------------|---------------------------|------------------------|------------------------|------------|
| 1 | Dashboard | `dashboard.php` | ✅ `/admin/dashboard.php` | ❌ `/admin/content/dashboard.php` | ❌ `/admin/messages/dashboard.php` | Prepend `../` from subdirs |
| 2 | News | `content/?type=post` | ✅ `/admin/content/?type=post` | ❌ `/admin/content/content/?type=post` | ❌ `/admin/messages/content/?type=post` | Prepend `../` from subdirs |
| 3 | Pages | `content/?type=page` | ✅ `/admin/content/?type=page` | ❌ duplicate | ❌ wrong dir | Prepend `../` from subdirs |
| 4 | Projects | `content/?type=project` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 5 | Services | `content/?type=service` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 6 | Activities | `content/?type=activity` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 7 | Awards | `content/?type=prize` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 8 | CSR | `content/?type=rse` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 9 | Resources | `content/?type=resource` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 10 | Sports | `content/?type=sport` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 11 | Videos | `content/?type=video` | ✅ | ❌ | ❌ | Prepend `../` from subdirs |
| 12 | Media | `media/` | ✅ `/admin/media/` | ❌ `/admin/content/media/` | ❌ `/admin/messages/media/` | Prepend `../` from subdirs |
| 13 | Messages | `messages/` | ✅ `/admin/messages/` | ❌ `/admin/content/messages/` | ❌ `/admin/messages/messages/` | Prepend `../` from subdirs |
| 14 | Settings | `settings/` | ✅ `/admin/settings/` | ❌ `/admin/content/settings/` | ❌ `/admin/messages/settings/` | Prepend `../` from subdirs |
| 15 | Users | `users/` | ✅ `/admin/users/` | ❌ `/admin/content/users/` | ❌ `/admin/messages/users/` | Prepend `../` from subdirs |
| 16 | View site | `../public/` | ✅ `/sepj-gabes/public/` | ❌ `/admin/public/` | ❌ `/admin/public/` | Compute correct depth |
| 17 | Logout | `logout.php` | ✅ `/admin/logout.php` | ❌ `/admin/content/logout.php` | ❌ `/admin/messages/logout.php` | Prepend `../` from subdirs |

### Admin Header (`admin/includes/header.php`)

| # | Label | Current href | From `/admin/dashboard.php` | From `/admin/content/` | Fix needed |
|---|-------|--------------|---------------------------|------------------------|------------|
| 1 | Logout icon | `logout.php` | ✅ `/admin/logout.php` | ❌ `/admin/content/logout.php` | Prepend `../` from subdirs |

### Admin Dashboard Quick Actions (`admin/dashboard.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | Create Post | `content/create.php?type=post` | `admin/content/create.php` | ✅ Yes | ✅ (from dashboard only) | None (dashboard-only) |
| 2 | Create Project | `content/create.php?type=project` | `admin/content/create.php` | ✅ Yes | ✅ | None |
| 3 | Create Activity | `content/create.php?type=activity` | `admin/content/create.php` | ✅ Yes | ✅ | None |
| 4 | Upload | `media/upload.php` | `admin/media/upload.php` | ✅ Yes | ✅ | None |
| 5 | Messages | `messages/` | `admin/messages/` | ✅ Yes | ✅ | None |
| 6 | Dashboard → Messages | `messages/` (line 230) | `admin/messages/` | ✅ Yes | ✅ | None |

### Admin Content Table Actions (`admin/content/index.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | Edit item | `edit.php?id={id}` | `admin/content/edit.php` | ✅ Yes | ✅ (same dir) | None |
| 2 | Toggle status | `toggle-status.php?id={id}&csrf_token=...` | `admin/content/toggle-status.php` | ✅ Yes | ✅ (same dir) | None |
| 3 | Delete item | `delete.php?id={id}&type={type}` | `admin/content/delete.php` | ✅ Yes | ✅ (same dir) | None |
| 4 | Add New button | `create.php?type={type}` | `admin/content/create.php` | ✅ Yes | ✅ (same dir) | None |

### Admin Media Actions (`admin/media/index.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | Upload button | `upload.php` | `admin/media/upload.php` | ✅ Yes | ✅ (same dir) | None |
| 2 | Edit media | `edit.php?id={id}` | `admin/media/edit.php` | ✅ Yes | ✅ (same dir) | None |
| 3 | Delete media | `delete.php?id={id}` | `admin/media/delete.php` | ✅ Yes | ✅ (same dir) | None |

### Admin Messages Actions (`admin/messages/index.php`)

| # | Label | Current href | Expected file | Exists? | Works? | Fix |
|---|-------|--------------|---------------|---------|--------|-----|
| 1 | View message | `view.php?id={id}` | `admin/messages/view.php` | ✅ Yes | ✅ (same dir) | None |
| 2 | Mark read | `update-status.php?id={id}&status=read&csrf_token=...` | `admin/messages/update-status.php` | ✅ Yes | ✅ (same dir) | None |
| 3 | Archive | `update-status.php?id={id}&status=archived&csrf_token=...` | `admin/messages/update-status.php` | ✅ Yes | ✅ (same dir) | None |
| 4 | Delete | `delete.php?id={id}&csrf_token=...` | `admin/messages/delete.php` | ✅ Yes | ✅ (same dir) | None |
| 5 | Status filter links | `?status=new` etc. | `admin/messages/index.php` | ✅ Yes | ✅ (same dir) | None |

---

## SUMMARY OF BROKEN ITEMS

| # | Issue | Severity | Fix |
|---|-------|----------|-----|
| 1 | `public/post.php` does not exist | **CRITICAL** | Create the file (news listing page) |
| 2 | Sidebar links broken from subdirectories (15 links) | **CRITICAL** | Add dynamic prefix to sidebar.php URLs |
| 3 | Sidebar "View site" broken from subdirectories | **HIGH** | Fix `../public/` path in sidebar.php |
| 4 | Sidebar "Logout" broken from subdirectories | **HIGH** | Fix `logout.php` path in sidebar.php |
| 5 | Header logout icon broken from subdirectories | **HIGH** | Fix `logout.php` path in header.php |

## FILES TO CREATE
- `public/post.php` — News listing page (template similar to `projects.php`)

## FILES TO MODIFY
- `admin/includes/sidebar.php` — Make all navigation paths root/depth-aware
- `admin/includes/header.php` — Fix logout link path
- `public/includes/nav.php` — After creating post.php, verify nav_news link works
- `public/includes/footer.php` — After creating post.php, verify footer news link works
- `public/index.php` — After creating post.php, verify hero/news links work