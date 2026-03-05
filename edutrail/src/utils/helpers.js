// 👉 Avatar Text Initials
export const getAvatarText = (name) => {
  const nameParts = name.trim().split(' ').filter(Boolean)

  const initials = nameParts.slice(0, 2).map((part) => part[0].toUpperCase())

  return initials.join('')
}

export const fileExtract = (event) => {
  return new Promise((resolve, reject) => {
    const { files } = event.target

    if (!files || files.length === 0) {
      return reject(new Error('No Files Selected'))
    }

    const fileReader = new FileReader()
    fileReader.readAsDataURL(files[0])

    fileReader.onload = () => {
      if (typeof fileReader.result === 'string') {
        resolve({ fileObject: files[0], fileUrl: fileReader.result })
      } else {
        reject(new Error('Failed to read file as Data URL'))
      }
    }

    fileReader.onerror = () => reject(new Error('Error reading file'))
  })
}

// 👉 Slug Name
export const getSlugText = (name) => {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
    .slice(0, 23)
}
