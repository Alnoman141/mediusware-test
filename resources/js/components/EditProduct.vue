<template>
  <section>
    <div class="row">
      <div class="col-md-6">
        <div class="card shadow mb-4">
          <div class="card-body">
            <div class="form-group">
              <label for="">Product Name</label>
              <input
                type="text"
                v-model="product_name"
                placeholder="Product Name"
                class="form-control"
              />
            </div>
            <div class="form-group">
              <label for="">Product SKU</label>
              <input
                type="text"
                v-model="product_sku"
                placeholder="Product Name"
                class="form-control"
              />
            </div>
            <div class="form-group">
              <label for="">Description</label>
              <textarea
                v-model="description"
                id=""
                cols="30"
                rows="4"
                class="form-control"
              ></textarea>
            </div>
          </div>
        </div>

        <div class="card shadow mb-4">
          <div
            class="
              card-header
              py-3
              d-flex
              flex-row
              align-items-center
              justify-content-between
            "
          >
            <h6 class="m-0 font-weight-bold text-primary">Media</h6>
          </div>
          <div class="card-body border">
            <vue-dropzone
              ref="myVueDropzone"
              id="dropzone"
              :options="dropzoneOptions"
            ></vue-dropzone>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card shadow mb-4">
          <div
            class="
              card-header
              py-3
              d-flex
              flex-row
              align-items-center
              justify-content-between
            "
          >
            <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
          </div>
          <div class="card-body">
            <div class="row" v-for="(item, index) in product_variant">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="">Option</label>
                  <select v-model="item.option" class="form-control">
                    <option v-for="variant in variants" :value="variant.id">
                      {{ variant.title }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label
                    v-if="product_variant.length != 1"
                    @click="
                      product_variant.splice(index, 1);
                      checkVariant;
                    "
                    class="float-right text-primary"
                    style="cursor: pointer"
                    >Remove</label
                  >
                  <label v-else for="">.</label>
                  <input-tag
                    v-model="item.tags"
                    @input="checkVariant"
                    class="form-control"
                  ></input-tag>
                </div>
              </div>
            </div>
          </div>
          <div
            class="card-footer"
            v-if="
              product_variant.length < variants.length &&
              product_variant.length < 3
            "
          >
            <button @click="newVariant" class="btn btn-primary">
              Add another option
            </button>
          </div>

          <div class="card-header text-uppercase">Preview</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <td>Variant</td>
                    <td>Price</td>
                    <td>Stock</td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="variant_price in product_variant_prices">
                    <td>{{ variant_price.title }}</td>
                    <td>
                      <input
                        type="text"
                        class="form-control"
                        v-model="variant_price.price"
                      />
                    </td>
                    <td>
                      <input
                        type="text"
                        class="form-control"
                        v-model="variant_price.stock"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary">
      Save
    </button>
    <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
  </section>
</template>

<script>
import vue2Dropzone from "vue2-dropzone";
import "vue2-dropzone/dist/vue2Dropzone.min.css";
import InputTag from "vue-input-tag";

export default {
  components: {
    vueDropzone: vue2Dropzone,
    InputTag,
  },
  props: {
    variants: {
      type: Array,
      required: true,
    },
    product: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      product_name: "",
      product_sku: "",
      description: "",
      images: [],
      product_variant: [],
      product_variant_prices: [],
      dropzoneOptions: {
        url: "https://httpbin.org/post",
        thumbnailWidth: 150,
        maxFilesize: 0.5,
        headers: { "My-Awesome-Header": "header value" },
      },
    };
  },
  mounted() {
    this.getVariant(this.variants, this.product);
    this.product_name = this.product.title;
    this.product_sku = this.product.sku;
    this.description = this.product.description;
    this.images = this.product.images;
  },
  methods: {
    getVariant(variants, product) {
      let product_variant = [];
      variants.forEach((variant) => {
        let v = {
          option: variant.id,
          tags: [],
        };
        variant.product_variants.forEach((element) => {
          if (element.product_id === product.id) {
            let ele = element;
            if (ele.variant_id === variant.id) {
              v.id = ele.id;
              v.tags.push(ele.variant);
            }
          }
        });
        product_variant.push(v);
      });
      this.product_variant = product_variant;
      let tags = [];
      let items = [];
      this.product_variant.filter((item) => {
        tags.push(item.tags);
      });
      this.getCombn(tags).forEach((item) => {
        items.push(item);
      });
      this.product.product_variant_price.forEach((element, index) => {
          
          let pro = {
            id: element.id,
            title: items[index],
            price: element.price,
            stock: element.stock,
          };
        this.product_variant_prices.push(pro);
      });
      // this.checkVariant();
    },

    // it will push a new object into product variant
    newVariant() {
      let all_variants = this.variants.map((el) => el.id);
      let selected_variants = this.product_variant.map((el) => el.option);
      let available_variants = all_variants.filter(
        (entry1) => !selected_variants.some((entry2) => entry1 == entry2)
      );

      this.product_variant.push({
        option: available_variants[0],
        tags: [],
      });
    },

    // check the variant and render all the combination
    checkVariant() {
      let tags = [];
      let items = [];
      this.product_variant.filter((item) => {
        tags.push(item.tags);
      });
      this.getCombn(tags).forEach((item) => {
        items.push(item);
      });
      this.product_variant_prices.forEach((element, index) => {
          let pro = {
            id: null,
            title: items[index],
            price: 0,
            stock: 0,
          };
        this.product_variant_prices.push(pro);
      });
    },

    // combination algorithm
    getCombn(arr, pre) {
      pre = pre || "";
      if (!arr.length) {
        return pre;
      }
      let self = this;
      let ans = arr[0].reduce(function (ans, value) {
        return ans.concat(self.getCombn(arr.slice(1), pre + value + "/"));
      }, []);
      return ans;
    },

    // store product into database
    saveProduct() {
      this.product.product_variant = this.product_variant;
      this.product.product_variant_price = this.product_variant_prices;
      axios
        .put("/product/"+this.product.id, this.product)
        .then((response) => {
          alert("Product saved successfully");
          this.$router.push("/product");
        })
        .catch((error) => {
          console.log(error);
        });

      console.log(product);
    },
  },
};
</script>
