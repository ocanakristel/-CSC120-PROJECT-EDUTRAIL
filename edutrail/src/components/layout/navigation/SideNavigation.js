// 👉 Main Navigation; Title, Icon
export const mainNav = [
  ['Your Tasks', 'mdi-notebook-multiple'],
  ['List of Subjects', 'mdi-bookshelf'], // Adjusted for subjects navigation
]

// 👉 Sub Navigations; Title, Icon, Subtitle, Redirect Path
export const menuItemsNav1 = [
  ['Assignments', 'mdi-pencil-box-outline'],
  ['Projects', 'mdi-book-multiple'],
]

// Only keep "List of Subjects" in the second group with its redirection
export const menuItemsNav2 = [
  ['Subjects', 'mdi-bookshelf', null, '/subjects'], // Redirects to /subjects
]
