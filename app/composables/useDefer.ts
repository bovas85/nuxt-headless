export const useDefer = (count = 10) => {
  const displayPriority = ref(0)

  onMounted(() => {
    const step = () => {
      requestAnimationFrame(() => {
        displayPriority.value++
        if (displayPriority.value < count) {
          step()
        }
      })
    }
    step()
  })

  const defer = (priority: number) => displayPriority.value >= priority

  return {
    displayPriority,
    defer
  }
}
