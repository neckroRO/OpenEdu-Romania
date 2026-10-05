import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import SmokeComponent from './fixtures/SmokeComponent.vue'

describe('frontend test setup', () => {
  it('mounts a Vue component in jsdom', () => {
    const wrapper = mount(SmokeComponent)

    expect(wrapper.get('[data-test="smoke"]').text()).toBe('OpenEdu România')
  })
})
